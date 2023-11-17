


<div class="container">
    <form method="POST" action="{{ url('/car') }}">
        @csrf

        <div class="mb-3">
            <label for="has_car" class="form-label">Do you have a car?</label>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="has_car" value="yes">
                <label class="form-check-label" for="has_car_yes">Yes</label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="radio" name="has_car" value="no">
                <label class="form-check-label" for="has_car_no">No</label>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Submit</button>
    </form>
</div>

