jQuery(document).ready(function($) {
    // Variable to track the current variation ID
    var currentVariationID = null;
    var prevCorrectValue = 0;

    function isCoefficient(coefficient, number) {
        if (!coefficient || coefficient <= 0)
            return true;
        else if (number === null || number < 0)
            return false;
        else if (number % coefficient === 0) {
            return true;
        }
        return false;
    }

    // Function to validate quantity
    function validateQuantity($qtyInput, variation_id) {
        var qty = parseInt($qtyInput.val(), 10);
        
        if (!variation_id || !quantityStepData.steps.hasOwnProperty(variation_id)) {
            // No specific price steps defined for this variation
            return true;
        }

        var allowedSteps = quantityStepData.steps[variation_id];
        var $qtyInput = $('form.cart').find('input.qty');
        if (!isCoefficient(allowedSteps, qty)) {
            // Invalid quantity
            alert(`مقدار وارد شده باید مضربی از ${allowedSteps} و بزرگتر از صفر باشد`);
            $qtyInput.val(prevCorrectValue);
            return false;
        }
        prevCorrectValue = qty;
        return true;
    }

    // Hook into WooCommerce's variation selection
    $(document).on('found_variation', function(event, variation) {
        currentVariationID = variation.variation_id;
        var $qtyInput = $('form.cart').find('input.qty');

        $('form.cart').find('input.qty').attr('min', 0);
        $('form.cart').find('input.qty').removeAttr('max');
        $('form.cart').find('input.qty').attr('value', 0);
        prevCorrectValue = 0;

        // Optionally, set the quantity to the first allowed step
        if (quantityStepData.steps.hasOwnProperty(currentVariationID)) {
            var allowedSteps = quantityStepData.steps[currentVariationID];
            $('form.cart').find('input.qty').attr('step', allowedSteps);
            $qtyInput.val(0);

            // Remove any existing error messages
            $qtyInput.next('.wpqs-error').remove();
        }
    });

    // Listen for quantity changes
    $('form.cart').on('change', 'input.qty', function() {
        var $this = $(this);
        validateQuantity($this, currentVariationID);
    });

    // Prevent non-numeric input
    $('form.cart').on('keypress', 'input.qty', function(e) {
        var charCode = (e.which) ? e.which : e.keyCode;
        // Allow backspace (8), delete (46), arrow keys (37-40)
        if ($.inArray(charCode, [8, 46, 37, 38, 39, 40]) !== -1) {
            return;
        }
        // Allow only numbers (48-57)
        if (charCode < 48 || charCode > 57) {
            e.preventDefault();
        }
    });

    // Optional: Handle variation resets
    $(document).on('reset_data', function() {
        currentVariationID = null;
        var $qtyInput = $('form.cart').find('input.qty');
        $qtyInput.next('.wpqs-error').remove();
    });
});
