// File: public/js/checkout-address-integration.js

/**
 * Checkout Address Integration
 * Handles integration between address management and checkout process
 */

document.addEventListener("DOMContentLoaded", function () {
    initializeCheckoutAddressIntegration();
});

function initializeCheckoutAddressIntegration() {
    // Initialize saved address selection styles
    updateSavedAddressStyles();

    // Auto-load primary address if user is authenticated
    const isAuthenticated =
        document.querySelector('meta[name="user-authenticated"]')?.content ===
        "true";
    if (isAuthenticated) {
        loadPrimaryAddressIfAvailable();
    }

    // Setup form validation for step 2
    setupStep2Validation();
}

function updateSavedAddressStyles() {
    const savedAddressInputs = document.querySelectorAll(
        'input[name="saved_address_id"]'
    );

    savedAddressInputs.forEach((input) => {
        input.addEventListener("change", function () {
            // Remove selection styles from all labels
            document
                .querySelectorAll("label[data-address-id]")
                .forEach((label) => {
                    label.classList.remove("border-orange-500", "bg-orange-50");
                    label.classList.add("border-gray-200");
                });

            // Add selection style to selected label
            const selectedLabel = this.closest("label");
            if (selectedLabel && this.value !== "new") {
                selectedLabel.classList.add(
                    "border-orange-500",
                    "bg-orange-50"
                );
                selectedLabel.classList.remove("border-gray-200");
            }
        });
    });
}

function loadPrimaryAddressIfAvailable() {
    const primaryAddressInput = document.querySelector(
        'input[name="saved_address_id"]:checked'
    );
    if (primaryAddressInput && primaryAddressInput.value !== "new") {
        loadSavedAddress(primaryAddressInput.value);
    }
}

function setupStep2Validation() {
    // Override the original nextStep function for step 2
    const originalContinueStep2 = document.getElementById("continue-step-2");
    if (originalContinueStep2) {
        originalContinueStep2.onclick = function () {
            if (validateStep2()) {
                // Trigger shipping calculation
                calculateShippingOptions();
                nextStep(3);
            }
        };
    }
}

function validateStep2() {
    let isValid = true;
    const errors = [];

    // Check if using saved address or new address
    const savedAddressInput = document.querySelector(
        'input[name="saved_address_id"]:checked'
    );

    if (!savedAddressInput) {
        // No address selection made, check new address form
        isValid = validateNewAddressForm(errors);
    } else if (savedAddressInput.value === "new") {
        // New address selected, validate new address form
        isValid = validateNewAddressForm(errors);
    } else {
        // Saved address selected, ensure it's loaded
        isValid = validateSavedAddressSelection(errors);
    }

    if (!isValid) {
        showValidationErrors(errors);
    }

    return isValid;
}

function validateNewAddressForm(errors) {
    let isValid = true;

    // Required fields validation
    const requiredFields = [
        { id: "recipient_name", name: "Recipient Name" },
        { id: "phone_recipient", name: "Recipient Phone" },
        { id: "street_address", name: "Street Address" },
        { id: "province_name", name: "Province" },
        { id: "city_name", name: "City" },
        { id: "subdistrict_name", name: "Subdistrict" },
        { id: "postal_code", name: "Postal Code" },
    ];

    requiredFields.forEach((field) => {
        const element = document.getElementById(field.id);
        if (!element || !element.value.trim()) {
            errors.push(field.name + " is required");
            isValid = false;

            // Add error styling
            if (element) {
                element.classList.add("border-red-500");
                element.addEventListener(
                    "input",
                    function () {
                        this.classList.remove("border-red-500");
                    },
                    { once: true }
                );
            }
        }
    });

    // Address label validation
    const addressLabel = document.querySelector(
        'input[name="address_label"]:checked'
    );
    if (!addressLabel) {
        errors.push("Please select address label (Kantor or Rumah)");
        isValid = false;
    }

    // Phone number format validation
    const phoneInput = document.getElementById("phone_recipient");
    if (phoneInput && phoneInput.value) {
        const phoneRegex = /^[0-9+\-\s\(\)]{10,}$/;
        if (!phoneRegex.test(phoneInput.value)) {
            errors.push(
                "Please enter a valid phone number (minimum 10 digits)"
            );
            isValid = false;
            phoneInput.classList.add("border-red-500");
        }
    }

    // Postal code format validation
    const postalInput = document.getElementById("postal_code");
    if (postalInput && postalInput.value) {
        const postalRegex = /^[0-9]{5}$/;
        if (!postalRegex.test(postalInput.value)) {
            errors.push("Postal code must be exactly 5 digits");
            isValid = false;
            postalInput.classList.add("border-red-500");
        }
    }

    return isValid;
}

function validateSavedAddressSelection(errors) {
    // Check if destination_id is set (indicates address is loaded)
    const destinationId = document.getElementById("destination_id");
    if (!destinationId || !destinationId.value) {
        errors.push(
            "Please wait for address to load or select a different address"
        );
        return false;
    }

    return true;
}

function showValidationErrors(errors) {
    // Remove existing error display
    const existingError = document.getElementById("step2-errors");
    if (existingError) {
        existingError.remove();
    }

    if (errors.length > 0) {
        const errorDiv = document.createElement("div");
        errorDiv.id = "step2-errors";
        errorDiv.className =
            "bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4";

        const errorList = document.createElement("ul");
        errors.forEach((error) => {
            const li = document.createElement("li");
            li.textContent = error;
            errorList.appendChild(li);
        });

        errorDiv.appendChild(errorList);

        // Insert error before the buttons
        const step2Section = document.getElementById("section-2");
        const buttonsDiv = step2Section.querySelector(".flex.space-x-4");
        if (buttonsDiv && buttonsDiv.parentNode) {
            buttonsDiv.parentNode.insertBefore(errorDiv, buttonsDiv);

            // Scroll to error
            errorDiv.scrollIntoView({ behavior: "smooth", block: "center" });
        }
    }
}

function calculateShippingOptions() {
    // Get destination data
    const destinationId = document.getElementById("destination_id")?.value;
    const provinceName = document.getElementById("province_name")?.value;
    const cityName = document.getElementById("city_name")?.value;

    if (!destinationId && !cityName) {
        console.warn("No destination data available for shipping calculation");
        return;
    }

    // Show loading state
    const shippingOptions = document.getElementById("shipping-options");
    const shippingLoading = document.getElementById("shipping-loading");

    if (shippingOptions && shippingLoading) {
        shippingOptions.classList.add("hidden");
        shippingLoading.classList.remove("hidden");

        // Call shipping calculation API
        calculateShipping()
            .then(() => {
                shippingLoading.classList.add("hidden");
                shippingOptions.classList.remove("hidden");
            })
            .catch((error) => {
                console.error("Shipping calculation failed:", error);
                shippingLoading.classList.add("hidden");
                shippingOptions.classList.remove("hidden");

                // Show error in shipping options
                shippingOptions.innerHTML =
                    '<div class="p-4 text-center text-red-500 border-2 border-dashed border-red-200 rounded-lg">' +
                    "<p>⚠️ Failed to calculate shipping options</p>" +
                    '<p class="text-sm">Please try selecting a different address</p>' +
                    "</div>";
            });
    }
}

// Global function to calculate shipping (should be defined in main checkout.js)
async function calculateShipping() {
    const destinationId = document.getElementById("destination_id")?.value;
    const destinationLabel =
        document.getElementById("legacy_destination_label")?.value || "";
    const totalWeight =
        document.querySelector('meta[name="total-weight"]')?.content || 1000;

    if (!destinationId) {
        throw new Error("No destination selected");
    }

    try {
        const response = await fetch("/checkout/calculate-shipping", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document.querySelector(
                    'meta[name="csrf-token"]'
                ).content,
                Accept: "application/json",
            },
            body: JSON.stringify({
                destination_id: destinationId,
                destination_label: destinationLabel,
                weight: parseInt(totalWeight),
            }),
        });

        const data = await response.json();

        if (response.ok && data.success && data.options) {
            displayShippingOptions(data.options);

            // Log successful calculation
            console.log("Shipping calculation successful:", {
                total_options: data.total_options,
                api_version: data.api_version,
                origin_city: data.origin_city_name,
            });
        } else {
            // Handle API errors
            const errorMessage = data.error || "No shipping options available";
            console.error("Shipping API error:", errorMessage);

            // Show error in shipping options
            const shippingOptions = document.getElementById("shipping-options");
            if (shippingOptions) {
                shippingOptions.innerHTML =
                    '<div class="p-4 text-center text-red-500 border-2 border-dashed border-red-200 rounded-lg">' +
                    "<p>⚠️ " +
                    errorMessage +
                    "</p>" +
                    '<p class="text-sm mt-2">Please try selecting a different address or contact support</p>' +
                    "</div>";
            }
            throw new Error(errorMessage);
        }
    } catch (error) {
        console.error("Shipping calculation error:", error);

        // Show generic error if specific error not handled above
        const shippingOptions = document.getElementById("shipping-options");
        if (shippingOptions && !shippingOptions.innerHTML.includes("⚠️")) {
            shippingOptions.innerHTML =
                '<div class="p-4 text-center text-red-500 border-2 border-dashed border-red-200 rounded-lg">' +
                "<p>⚠️ Connection error</p>" +
                '<p class="text-sm mt-2">Please check your internet connection and try again</p>' +
                "</div>";
        }
        throw error;
    }
}

function displayShippingOptions(options) {
    const shippingOptions = document.getElementById("shipping-options");
    if (!shippingOptions) return;

    let optionsHtml = "";

    if (options.length === 0) {
        optionsHtml =
            '<div class="p-4 text-center text-gray-500 border-2 border-dashed border-gray-200 rounded-lg">' +
            "<p>📦 No shipping options available for this location</p>" +
            "</div>";
    } else {
        options.forEach((option, index) => {
            const isChecked = index === 0 ? "checked" : "";
            const cost = parseInt(option.cost || 0);
            const formattedCost =
                cost === 0
                    ? "Free"
                    : option.formatted_cost ||
                      "Rp " + cost.toLocaleString("id-ID");
            const isRecommended = option.recommended || false;
            const isMock = option.type === "mock";

            optionsHtml +=
                '<label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 shipping-option ' +
                (isChecked ? "border-blue-500 bg-blue-50" : "border-gray-200") +
                '">' +
                '<input type="radio" name="shipping_option" value="' +
                (option.display_name || option.service) +
                '" ' +
                'data-cost="' +
                cost +
                '" data-etd="' +
                (option.etd || "N/A") +
                '" ' +
                isChecked +
                ' class="mr-4">' +
                '<div class="flex-1 shipping-content">' +
                '<div class="font-medium">' +
                (option.display_name ||
                    option.courier + " - " + option.service) +
                (isRecommended
                    ? ' <span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded">Recommended</span>'
                    : "") +
                (isMock
                    ? ' <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded">Estimate</span>'
                    : "") +
                "</div>" +
                '<div class="text-sm text-gray-600">' +
                (option.description || "Standard shipping") +
                "</div>" +
                '<div class="text-sm text-gray-500">Estimated: ' +
                (option.etd || "N/A") +
                " day(s)</div>" +
                "</div>" +
                '<div class="font-medium text-blue-600">' +
                formattedCost +
                "</div>" +
                "</label>";
        });
    }

    shippingOptions.innerHTML = optionsHtml;

    // Add event listeners for shipping option selection
    const shippingInputs = shippingOptions.querySelectorAll(
        'input[name="shipping_option"]'
    );
    shippingInputs.forEach((input) => {
        input.addEventListener("change", function () {
            // Update hidden fields
            document.getElementById("shipping_method").value = this.value;
            document.getElementById("shipping_cost").value = this.dataset.cost;

            // Update shipping display in order summary
            updateShippingCostDisplay(parseInt(this.dataset.cost));

            // Update selection styles
            shippingOptions
                .querySelectorAll(".shipping-option")
                .forEach((option) => {
                    option.classList.remove("border-blue-500", "bg-blue-50");
                    option.classList.add("border-gray-200");
                });

            this.closest(".shipping-option").classList.add(
                "border-blue-500",
                "bg-blue-50"
            );
            this.closest(".shipping-option").classList.remove(
                "border-gray-200"
            );
        });
    });

    // Set default shipping cost if first option is selected
    if (options.length > 0) {
        const firstOption = options[0];
        document.getElementById("shipping_method").value =
            firstOption.display_name || firstOption.service;
        document.getElementById("shipping_cost").value = parseInt(
            firstOption.cost || 0
        );
        updateShippingCostDisplay(parseInt(firstOption.cost || 0));
    }
}

function updateShippingCostDisplay(cost) {
    const shippingCostDisplay = document.getElementById(
        "shipping-cost-display"
    );
    const totalDisplay = document.getElementById("total-display");
    const subtotal = parseInt(
        document.querySelector('meta[name="cart-subtotal"]')?.content || 0
    );
    const tax = Math.round(subtotal * 0.11);

    if (shippingCostDisplay) {
        shippingCostDisplay.textContent =
            cost === 0 ? "Free" : "Rp " + cost.toLocaleString("id-ID");
    }

    if (totalDisplay) {
        const total = subtotal + cost + tax;
        totalDisplay.textContent = "Rp " + total.toLocaleString("id-ID");
    }
}
