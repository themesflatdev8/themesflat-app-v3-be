<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

abstract class AbstractService
{
    public $status;
    public $statusCode;
    public $message;
    public $data;
    public $sentryId;
    public $errors;
    protected $model;
    protected $modelId;
    protected $dataRequest;

    /**
     * Set status
     *
     * @param boolean $status
     * @return $this
     */
    public function setStatus($status = true)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Set status code
     *
     * @param boolean $statusCode
     * @return $this
     */
    public function setStatusCode($statusCode = true)
    {
        $this->statusCode = $statusCode;

        return $this;
    }

    /**
     * Set message
     *
     * @param string $message
     * @return $this
     */
    public function setMessage($message = '')
    {
        $this->message = $message;

        return $this;
    }

    /**
     * Set data
     *
     * @param object|array|string|integer $data
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }


    public function setSentryId($sentryId)
    {
        $this->sentryId = $sentryId;

        return $this;
    }


    /**
     * @param $errors
     * @return $this
     */
    public function setErrors($errors)
    {
        $this->errors = $errors;

        return $this;
    }

    /**
     * Retrive current status
     *
     * @return boolean
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Retrive current message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Retrive result of service
     *
     * @return array
     */
    public function getResult()
    {
        return $this->getStatus() ? $this->getData() : [];
    }

    /**
     * Retrive default value of service
     *
     * @return array
     */
    public function getData($key = null)
    {
        $result = empty($this->data) ? [] : $this->data;

        if (!empty($key) && !empty($this->data)) {
            $result = $this->data[$key];
        }

        return $result;
    }

    /**
     * Retrive default value of service
     *
     * @return array
     */
    public function getSentryId()
    {
        return $this->sentryId;
    }

    /**
     * @return mixed
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * @return mixed
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * Set the handler
     *
     * @param \Illuminate\Database\Eloquent\Model|int|string $model
     * @return self
     */
    public function setModel($model)
    {
        if ($model instanceof Model) {
            $this->model = $model;
        }

        if (!$model instanceof Model) {
            $this->modelId = $model;
        }

        return $this;
    }

    public function setRequest($request)
    {
        $result = array_merge(
            [
                'storeInfo' => $request->storeInfo,
            ],
            $request->validated()
        );

        if (!empty($request->filters)) {
            $filters = is_array($request->filters) ? $request->filters : (array)json_decode($request->filters);

            $result = array_merge($result, [
                'filters' => $filters
            ]);
        }

        $this->dataRequest = $result;

        return $this;
    }

    public function setDataRequest(array $data)
    {
        $this->dataRequest = $data;

        return $this;
    }

    /**
     * Get default pagination limit
     *
     * @return integer
     */
    protected function getPerPage()
    {
        return $this->dataRequest['per_page'] ?? 10;
    }
}
