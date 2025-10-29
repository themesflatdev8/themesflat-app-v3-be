<div class="container">
  <div class="c-productReview" data-product-id="{{ $productId }}" data-shop="{{ $shopDomain }}">

    <div class="head">
      <div class="rate-wrap">
        <div class="c-productReviews">
          <div class="star_wrap">
            <div class="text-center">
              <div class="number-review" style="text-align: center;">
                  <img src="{{ asset('images/star.png') }}" style="    width: 78px;border-radius: 18px;text-align: center;" alt="Logo">
              </div>

              <div class="list-star-default">
                  <div class="star-rating" aria-label="{{ $avgRating }} stars">
                      @for($i = 1; $i <= 5; $i++)
                      @php
                          $fill = 0; // 0 = xám, 1 = vàng đầy, 0.5 = nửa vàng
                          if ($avgRating >= $i) {
                              $fill = 1; // đầy vàng
                          } elseif ($avgRating > $i - 1) {
                              $fill = $avgRating - ($i - 1); // phần còn lại
                          }
                      @endphp
                      <svg class="star" viewBox="0 0 24 24">
                          <defs>
                          <linearGradient id="grad{{ $i }}">
                              <stop offset="{{ $fill * 100 }}%" stop-color="#FFD700"/>
                              <stop offset="{{ $fill * 100 }}%" stop-color="#ccc"/>
                          </linearGradient>
                          </defs>
                          <path d="M12 17.75l-5.09 3.01 1.47-6.14-4.68-4.07
                                  6.18-.53L12 2l2.12 7.02 6.18.53-4.68 4.07
                                  1.47 6.14z" fill="url(#grad{{ $i }})"/>
                      </svg>
                      @endfor
                  </div>
              </div>
              <div class="average-rating" style="text-align: center;">
                  {{ count($reviews) }} Reviews
              </div>
            </div>
            <div class="lstSum">
              @php
                  // đảm bảo $summary và $totalReviews tồn tại
                  $totalReviews = count($reviews);
                  $summary = $summary ?? [5=>0,4=>0,3=>0,2=>0,1=>0];
                  $totalReviews = $totalReviews ?? array_sum($summary);
              @endphp

              @foreach(range(5, 1) as $star)
                  @php
                  $count = isset($summary[$star]) ? (int)$summary[$star] : 0;
                  $percent = $totalReviews > 0 ? ($count / $totalReviews) * 100 : 0;
                  // tránh số quá dài, giới hạn 2 chữ số thập phân và không vượt 100
                  $percent = min(100, round($percent, 2));
                  // To string for inline style (Blade sẽ escape %, nhưng đây ok)
                  $percentStyle = $percent . '%';
                  @endphp

                  <div class="item" data-star="{{ $star }}">
                  <div class="number-1 text-caption-1">{{ $star }}</div>

                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
                      width="20" height="20" class="star-icon" aria-hidden="true">
                      <path d="M12 17.75l-5.09 3.01 1.47-6.14-4.68-4.07 6.18-.53L12 2l2.12 7.02 6.18.53-4.68 4.07 1.47 6.14z"
                          fill="#FFD700"/>
                  </svg>

                  <div class="line-bg" role="progressbar" aria-valuenow="{{ $percent }}" aria-valuemin="0" aria-valuemax="100">
                      <div class="line-fill" style="width: {{ $percentStyle }}; background: #FFD700;height: 8px;"></div>
                  </div>

                  <div class="number-2 text-caption-1">{{ $count }}</div>
                  </div>
              @endforeach
            </div>
          </div>
        </div>
      </div>
      <div class="more">
        <span class="tf-btn c-btnReview">Write a review</span>
        <span class="tf-btn c-btnCancelReview" style="display: none;">Cancel Review</span>
      </div>
    </div>
    <div class="list">
      {{-- <h4 class="count-number"><span id="count-number">{{ count($reviews) }}</span> Comments</h4> --}}
      <div class="review-container">
        @foreach($reviews as $r)
          <div class="review-item">
            <div class="infor">
              <div class="avatar"></div>
              <div class="content">
                <h6 class="review-author">
                  {{ $r['user_name'] }}
                  <span>{{ str_repeat('★', $r['rating']) }}</span>
                </h6>
                <div class="review-date">
                  {{ \Carbon\Carbon::parse($r['created_at'])->format('F j, Y') }}
                </div>
              </div>
            </div>
            <div class="review-text">{{ $r['review_text'] }}</div>
          </div>
        @endforeach
      </div>
    </div>
    <div class="form">
      <!-- form submit review -->
      <form id="review-form" class="review-form" method="POST" action="">
        @csrf

        <div class="heading">
          <h4>{{ __('Write a review') }}</h4>
          <input type="hidden" name="domain_name" value="{{ $shopDomain }}">
          <input type="hidden" name="product_id" value="{{ $productId }}">

          <!-- Rating stars -->
          <div class="rating-stars">
            <input type="radio" name="rating" id="star5" value="5"><label for="star5"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="star-icon">
                <path d="M12 17.75l-5.09 3.01 1.47-6.14-4.68-4.07 6.18-.53L12 2l2.12 7.02 6.18.53-4.68 4.07 1.47 6.14z" fill="currentColor"/>
              </svg></label>
            <input type="radio" name="rating" id="star4" value="4"><label for="star4"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="star-icon">
                <path d="M12 17.75l-5.09 3.01 1.47-6.14-4.68-4.07 6.18-.53L12 2l2.12 7.02 6.18.53-4.68 4.07 1.47 6.14z" fill="currentColor"/>
              </svg></label>
            <input type="radio" name="rating" id="star3" value="3"><label for="star3"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="star-icon">
                <path d="M12 17.75l-5.09 3.01 1.47-6.14-4.68-4.07 6.18-.53L12 2l2.12 7.02 6.18.53-4.68 4.07 1.47 6.14z" fill="currentColor"/>
              </svg></label>
            <input type="radio" name="rating" id="star2" value="2"><label for="star2"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="star-icon">
                <path d="M12 17.75l-5.09 3.01 1.47-6.14-4.68-4.07 6.18-.53L12 2l2.12 7.02 6.18.53-4.68 4.07 1.47 6.14z" fill="currentColor"/>
              </svg></label>
            <input type="radio" name="rating" id="star1" value="1"><label for="star1"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="star-icon">
                <path d="M12 17.75l-5.09 3.01 1.47-6.14-4.68-4.07 6.18-.53L12 2l2.12 7.02 6.18.53-4.68 4.07 1.47 6.14z" fill="currentColor"/>
              </svg></label>
          </div>
        </div>

        <!-- Review Title -->
        <div class="form-group">
          <label for="review_title">{{ __('Title') }}</label>
          <input type="text" id="review_title" name="review_title" placeholder="{{ __('Enter title') }}" value="{{ old('review_title') }}">
        </div>

        <!-- Review Body -->
        <div class="form-group">
          <label for="review_text">{{ __('Review') }}</label>
          <textarea id="review_text" name="review_text" placeholder="{{ __('Write your review') }}">{{ old('review_text') }}</textarea>
        </div>

        <!-- Name and Email -->
        <div class="form-row">
          <input type="text" id="user_name" name="user_name" placeholder="{{ __('Your name') }}" value="{{ old('user_name') }}">
          <input type="email" id="user_email" name="user_email" placeholder="{{ __('Your email') }}" value="{{ old('user_email') }}">
        </div>

        <!-- Remember me -->
        <div class="form-group checkbox-group">
          <input type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
          <label for="remember">{{ __('Notify me about replies') }}</label>
        </div>

        <!-- Submit button -->
        <button type="submit" class="submit-review">{{ __('Submit Review') }}</button><div class="text-notification"></div>
      </form>
    </div>
  </div>
</div>
<style>

    .lstSum {
  display: flex;
  flex-direction: column;
  gap: 10px;
  width: 100%; /* hoặc cố định như bạn muốn */
  max-width: 360px;
}

.item {
  display: flex;
  align-items: center;
  gap: 8px;
}

.number-1 {
  width: 18px;
  text-align: center;
  font-size: 13px;
}

.star-icon {
  flex: 0 0 20px;
}



.line-fill {
  height: 100%;
  width: 0%;
  background: linear-gradient(90deg, #ffc107, #ffb300); /* vàng */
  transition: width 400ms ease;
  border-radius: 6px 0 0 6px;
}

.number-2 {
  width: 28px;
  text-align: right;
  font-size: 13px;
}
.line-bg {
  position: relative;
  width: 100%;
  max-width: 200px; /* tùy bạn */
  height: 10px;
  background: #eee;
  border-radius: 5px;
  overflow: hidden;
  display: block;
}

.line-bg div {
  position: absolute;
  top: 0;
  left: 0;
  height: 100%;
  background: #f8b400; /* màu vàng sao */
  transition: width 0.3s ease-in-out;
  display: block !important;
}

.c-productReviews .star_wrap {
    display: contents!important;
}



/* stat */

.list-star-default {
  display: inline-block;
}

.star-rating {
  display: flex;
  gap: 2px;
}

.star {
  width: 20px;
  height: 20px;
}

/* rating total */
.review-badge {
  width: 40px;
  height: 40px;
  background: url('review-icon.png') no-repeat center;
  background-size: contain;
  position: relative;
}
.review-badge::after {
  content: attr(data-review);
  position: absolute;
  bottom: -5px;
  right: -5px;
  background: red;
  color: #fff;
  font-size: 12px;
  border-radius: 50%;
  padding: 2px 5px;
}
</style>
