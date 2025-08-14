<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AffiliateModel;

use App\Http\Requests\Admin\AffiliateRequest;
use Illuminate\Http\Request;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Redis;

class AffiliateController extends Controller
{
    /**
     * Display and search value affiliate
     * GET /affiliate
     */
    public function index(Request $request)
    {
        $query = AffiliateModel::query();
        $affiliateStatus = Redis::get('fether_extension');
        // Check if keyword exists in the request
        if ($request->has('keyword')) {
            $keyword = $request->input('keyword');

            // Search for records that match the keyword in domain, iframe, or cookie_name
            $query->where(function ($query) use ($keyword) {
                $query->where('domain', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('iframe', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('cookie_name', 'LIKE', '%' . $keyword . '%');
            });
        }

        // Paginate the results
        $affiliate = $query->orderBy('id', 'DESC')->paginate(10);

        return view('affiliate.affiliateList', compact('affiliate', 'affiliateStatus'));
    }

    /**
     * Display add affiliate form
     * GET /affiliate/add
     */
    public function add()
    {
        return view('affiliate/add');
    }

    /**
     * Add new affiliate item
     * POST /black-list/add
     */
    public function post(AffiliateRequest $request)
    {
        $validatedData = $request->validated();

        if (is_array($validatedData['iframe'])) {
            $validatedData['iframe'] = json_encode($validatedData['iframe']);
        }

        if (empty($validatedData['timeout'])) {
            $validatedData['timeout'] = 86400;
        }

        AffiliateModel::create($validatedData);

        Redis::set('afflidate_data', null);

        return redirect()->back()->with('success', 'Affiliate item added successfully.');
    }

    /**
     * Get affiliate item to edit
     * GET /affiliate/edit/:id
     * @param integer $id
     */
    public function edit($id)
    {
        $affiliateItem = AffiliateModel::where('id', $id)->first();

        if (!$affiliateItem) {
            return redirect()->back()->with('error', 'Affiliate item not found.');
        }

        $iframeData = $affiliateItem->iframe;

        // Decode JSON if necessary and ensure that $iframeArray is always an array
        $iframeArray = [];
        if (is_string($iframeData)) {
            $decodedJson = json_decode($iframeData, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $iframeArray = $decodedJson; // If JSON is valid, use array
            } else {
                $iframeArray = [$iframeData]; // If not JSON, convert it to an array with one element
            }
        } elseif (is_array($iframeData)) {
            $iframeArray = $iframeData;
        }

        return view('affiliate.edit', compact('affiliateItem', 'iframeArray'));
    }

    /**
     * Update affiliate item
     * POST /affiliate/edit/:id
     * @param integer $id
     */
    public function update($id, AffiliateRequest $request)
    {
        $affiliateItem = AffiliateModel::find($id);

        if (!$affiliateItem) {
            return redirect()->back()->with('error', 'Affiliate item not found.');
        }

        $data = $request->validated();

        // Check and process iframe data
        $data['iframe'] = isset($data['iframe']) && is_array($data['iframe'])
            ? json_encode(array_filter($data['iframe']))
            : json_encode([]);

        $affiliateItem->update($data);

        Redis::set('afflidate_data', null);

        return redirect()->back()->with('success', 'Update affiliate success');
    }

    /**
     * Delete a affliate item
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete($id)
    {
        $affiliateItem = AffiliateModel::find($id);

        if ($affiliateItem) {
            $affiliateItem->delete();

            Redis::set('afflidate_data', null);
            return redirect()->back()->with('success', 'Affiliate item deleted successfully.');
        }

        return redirect()->back()->with('error', 'Affiliate item not found.');
    }

    /**
     * Toggle affiliate
     */
    public function toggleAffiliate()
    {
        // Get the current state from Redis
        $currentStatus = Redis::get('fether_extension');

        $newStatus = ($currentStatus == 'on') ? 'off' : 'on';
        // Set new state for redis
        Redis::set('fether_extension', $newStatus);

        return redirect()->back()->with('success', 'Handle affiliate success');
    }

    /*
    * Chunking Csv file for affiliate
    */
    public function downloadSampleCsv()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="sample_affiliate.csv"',
        ];
        $columns = ['domain', 'iframe', 'cookie_name'];

        $callback = function () use ($columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);
            fputcsv($file, ['example.com', 'https://example.com/iframe', 'example_cookie']);
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /*
    * Chunking Csv file for affiliate
    */
    public function chunking(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|mimes:csv,txt',
        ]);

        // Lấy đường dẫn thực của tệp tin đã tải lên
        $filePath = $request->file('csv_file')->getRealPath();

        $data = file($filePath);

        $chunks = array_chunk($data, 100);
        $part = storage_path('temp');

        foreach ($chunks as $key => $chunk) {
            $csv_file = $part . "/csv-chunk-{$key}-" . Str::random(20) . ".csv";
            file_put_contents($csv_file, $chunk);
        }

        return redirect()->route('upload_csv');
    }

    /*
    * Upload CSV file
    */
    public function uploadCsv()
    {
        $part = storage_path('temp');
        $files = glob($part . '/*.csv');

        foreach ($files as $key => $file) {
            try {
                $csv = array_map('str_getcsv', file($file));
                if ($key == 0) {
                    $header = $csv[0];
                    $header = array_map(function ($value) {
                        // Remove BOM
                        return str_replace(' ', '_', strtolower(preg_replace('/\x{FEFF}/u', '', $value)));
                    }, $header);

                    unset($csv[0]);
                }

                foreach ($csv as $key => $value) {
                    $record = array_combine($header, $value);

                    $fieldsToInsert = array_intersect(['domain', 'iframe', 'cookie_name'], array_keys($record));
                    $dataToInsert = array_intersect_key($record, array_flip($fieldsToInsert));
                    if (empty($dataToInsert)) {
                        File::delete($file);
                        throw new \Exception('Invalid file data');
                    }
                    AffiliateModel::create($dataToInsert);
                }

                if (File::exists($file)) {
                    File::delete($file);
                }
            } catch (\Exception $e) {
                $errors[] = "Error processing file $file: " . $e->getMessage();
            }

            if (!empty($errors)) {
                return redirect()->route('affiliate')->withErrors($errors);
            }

            Redis::set('afflidate_data', null);

            return redirect()->route('affiliate')->with('success', 'CSV imported successfully!');
        }
    }
}
