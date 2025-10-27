<div class="c-productReview" data-product-id="{{ $productId }}" data-shop="{{ $shopDomain }}" data-handle="{{ product.handle }}">>
  <div class="head">
    <div class="rate-wrap">
      <div class="avg-rating">
        {{ number_format($avgRating, 1) }} ★
      </div>
      <div class="review-count">({{ count($reviews) }} reviews)</div>
    </div>
    <div class="more">
      <span class="tf-btn c-btnReview">Write a review</span>
      <span class="tf-btn c-btnCancelReview">Cancel</span>
    </div>
  </div>

  <div class="list">
    <h4 class="count-number"><span id="count-number">{{ count($reviews) }}</span> Comments</h4>
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
    <form id="review-form" class="review-form" method="POST" action="{{ route('reviews.add') }}">
      @csrf

      <div class="heading">
        <h4>{{ __('Write a review') }}</h4>
        <input type="hidden" name="domain_name" value="{{ $shopDomain }}">
        <input type="hidden" name="product_id" value="{{ $productId }}">
        <input type="hidden" name="handle" value="{{ $handle }}">

        <!-- Rating stars -->
        <div class="rating-stars">
          <input type="radio" name="rating" id="star5" value="5"><label for="star5"></label>
          <input type="radio" name="rating" id="star4" value="4"><label for="star4"></label>
          <input type="radio" name="rating" id="star3" value="3"><label for="star3"></label>
          <input type="radio" name="rating" id="star2" value="2"><label for="star2"></label>
          <input type="radio" name="rating" id="star1" value="1"><label for="star1"></label>
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
      <button type="submit" class="submit-review">{{ __('Submit Review') }}</button>
    </form>
  </div>
</div>
