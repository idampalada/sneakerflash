// File: public/js/checkout-address-complete.js
// Complete checkout address integration with validation fixes

document.addEventListener("DOMContentLoaded", function () {
    console.log("🚀 Checkout address integration initialized");

    // Initialize address integration
    initializeAddressIntegration();

    // Override continue button for step 2
    const continueStep2Btn = document.getElementById("continue-step-2");
    if (continueStep2Btn) {
        continueStep2Btn.onclick = function (e) {
            e.preventDefault();
            console.log("Step 2 continue clicked");

            if (validateStep2Complete()) {
                nextStepWithValidation(3);
            }
        };
    }
});

function initializeAddressIntegration() {
    const locationSearch = document.getElementById("location_search");
    const locationResults = document.getElementById("location-results");
    const selectedLocation = document.getElementById("selected-location");
    const selectedLocationText = document.getElementById(
        "selected-location-text"
    );

    let searchTimeout;

    // Set default address label to "Rumah" if none selected
    setDefaultAddressLabel();

    // Auto-load primary address if user is authenticated and has saved addresses
    autoLoadPrimaryAddress();

    // Location search functionality
    if (locationSearch) {
        locationSearch.addEventListener("input", function () {
            const query = this.value.trim();

            clearTimeout(searchTimeout);

            if (query.length < 2) {
                hideLocationResults();
                return;
            }

            searchTimeout = setTimeout(() => {
                searchLocation(query);
            }, 300);
        });
    }

    // Hide results when clicking outside
    document.addEventListener("click", function (e) {
        if (locationSearch && locationResults) {
            if (
                !locationSearch.contains(e.target) &&
                !locationResults.contains(e.target)
            ) {
                hideLocationResults();
            }
        }
    });
}

function setDefaultAddressLabel() {
    if (!document.querySelector('input[name="address_label"]:checked')) {
        const rumahOption = document.querySelector(
            'input[name="address_label"][value="Rumah"]'
        );
        if (rumahOption) {
            rumahOption.checked = true;
            updateAddressLabelStyles();
        }
    }
}

function autoLoadPrimaryAddress() {
    // Check if user is authenticated and has saved addresses
    const isAuthenticated =
        document.querySelector('meta[name="user-authenticated"]')?.content ===
        "true";

    if (isAuthenticated) {
        const primaryAddressInput = document.querySelector(
            'input[name="saved_address_id"]:checked'
        );
        if (primaryAddressInput && primaryAddressInput.value !== "new") {
            console.log(
                "Auto-loading primary address:",
                primaryAddressInput.value
            );
            loadSavedAddress(primaryAddressInput.value);
        }
    }
}

async function searchLocation(query) {
    console.log("Searching location:", query);

    try {
        const response = await fetch(
            `/checkout/search-destinations?search=${encodeURIComponent(
                query
            )}&limit=10`,
            {
                headers: {
                    Accept: "application/json",
                    "X-Requested-With": "XMLHttpRequest",
                    "X-CSRF-TOKEN": document.querySelector(
                        'meta[name="csrf-token"]'
                    ).content,
                },
            }
        );

        if (response.ok) {
            const data = await response.json();
            displayLocationResults(data.data || []);
        } else {
            console.error("Location search failed:", response.status);
            hideLocationResults();
        }
    } catch (error) {
        console.error("Location search error:", error);
        hideLocationResults();
    }
}

function displayLocationResults(locations) {
    const locationResults = document.getElementById("location-results");
    if (!locationResults) return;

    if (locations.length === 0) {
        hideLocationResults();
        return;
    }

    locationResults.innerHTML = "";

    locations.forEach((location) => {
        const item = document.createElement("div");
        item.className =
            "p-3 hover:bg-gray-50 cursor-pointer border-b border-gray-100 last:border-b-0";
        item.innerHTML = `
            <div class="font-medium text-gray-900">${
                location.display_name || location.subdistrict_name
            }</div>
            <div class="text-sm text-gray-600">${
                location.full_address || location.label
            }</div>
        `;

        item.addEventListener("click", () => selectLocation(location));
        locationResults.appendChild(item);
    });

    locationResults.classList.remove("hidden");
}

function selectLocation(location) {
    console.log("Location selected:", location);

    // Fill hidden fields
    setFieldValue("province_name", location.province_name || "");
    setFieldValue("city_name", location.city_name || "");
    setFieldValue("subdistrict_name", location.subdistrict_name || "");
    setFieldValue(
        "postal_code",
        location.zip_code || location.postal_code || ""
    );
    setFieldValue(
        "destination_id",
        location.location_id || location.destination_id || ""
    );

    // Fill legacy fields for backward compatibility
    const fullAddress =
        location.full_address ||
        location.label ||
        `${location.subdistrict_name}, ${location.city_name}, ${location.province_name}`;
    setFieldValue("legacy_address", fullAddress);
    setFieldValue("legacy_destination_label", fullAddress);

    // Display selected location
    const selectedLocationText = document.getElementById(
        "selected-location-text"
    );
    const selectedLocation = document.getElementById("selected-location");

    if (selectedLocationText && selectedLocation) {
        selectedLocationText.textContent = fullAddress;
        selectedLocation.classList.remove("hidden");
    }

    // Hide search results and clear search input
    hideLocationResults();
    const locationSearch = document.getElementById("location_search");
    if (locationSearch) {
        locationSearch.value = "";
    }
}

function setFieldValue(fieldId, value) {
    const field = document.getElementById(fieldId);
    if (field) {
        field.value = value;
    }
}

function hideLocationResults() {
    const locationResults = document.getElementById("location-results");
    if (locationResults) {
        locationResults.classList.add("hidden");
    }
}

function loadSavedAddress(addressId) {
    if (addressId === "new") {
        showNewAddressForm();
        return;
    }

    console.log("Loading saved address:", addressId);

    // Hide new address form
    const newAddressForm = document.getElementById("new-address-form");
    if (newAddressForm) {
        newAddressForm.classList.add("hidden");
    }

    // Fetch address data
    fetch(`/profile/addresses/${addressId}/show`, {
        headers: {
            Accept: "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')
                .content,
        },
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                const address = data.address;
                console.log("Address loaded:", address);

                // Fill form fields with address data
                setFieldValue("recipient_name", address.recipient_name);
                setFieldValue("phone_recipient", address.phone_recipient);
                setFieldValue("street_address", address.street_address);

                // Fill hidden location fields
                setFieldValue("province_name", address.province_name);
                setFieldValue("city_name", address.city_name);
                setFieldValue("subdistrict_name", address.subdistrict_name);
                setFieldValue("postal_code", address.postal_code);
                setFieldValue("destination_id", address.destination_id || "");

                // Fill legacy fields for backward compatibility
                setFieldValue("legacy_address", address.full_address);
                setFieldValue(
                    "legacy_destination_label",
                    address.location_string
                );

                // Show selected location
                const selectedLocationText = document.getElementById(
                    "selected-location-text"
                );
                const selectedLocationDiv =
                    document.getElementById("selected-location");

                if (selectedLocationText && selectedLocationDiv) {
                    selectedLocationText.textContent = address.location_string;
                    selectedLocationDiv.classList.remove("hidden");
                }

                // Set address label
                const labelInput = document.querySelector(
                    `input[name="address_label"][value="${address.label}"]`
                );
                if (labelInput) {
                    labelInput.checked = true;
                    updateAddressLabelStyles();
                }

                // Clear save options since this is existing address
                const saveCheckbox = document.querySelector(
                    'input[name="save_address"]'
                );
                const primaryCheckbox = document.querySelector(
                    'input[name="set_as_primary"]'
                );
                if (saveCheckbox) saveCheckbox.checked = false;
                if (primaryCheckbox) primaryCheckbox.checked = false;
            } else {
                console.error("Failed to load address:", data.message);
                showNewAddressForm();
            }
        })
        .catch((error) => {
            console.error("Error loading address:", error);
            showNewAddressForm();
        });
}

function showNewAddressForm() {
    console.log("Showing new address form");

    const newAddressForm = document.getElementById("new-address-form");
    if (newAddressForm) {
        newAddressForm.classList.remove("hidden");
    }

    // Clear form fields
    setFieldValue("recipient_name", getUserName());
    setFieldValue("phone_recipient", getUserPhone());
    setFieldValue("street_address", "");

    // Clear location fields
    setFieldValue("province_name", "");
    setFieldValue("city_name", "");
    setFieldValue("subdistrict_name", "");
    setFieldValue("postal_code", "");
    setFieldValue("destination_id", "");
    setFieldValue("legacy_address", "");
    setFieldValue("legacy_destination_label", "");

    // Hide selected location
    const selectedLocation = document.getElementById("selected-location");
    if (selectedLocation) {
        selectedLocation.classList.add("hidden");
    }

    // Reset address label to default
    const rumahOption = document.querySelector(
        'input[name="address_label"][value="Rumah"]'
    );
    if (rumahOption) {
        rumahOption.checked = true;
        updateAddressLabelStyles();
    }

    // Enable save options
    const saveCheckbox = document.querySelector('input[name="save_address"]');
    if (saveCheckbox) {
        saveCheckbox.checked = true;
    }
}

function getUserName() {
    // Get user name from existing fields if available
    const firstName = document.getElementById("first_name")?.value || "";
    const lastName = document.getElementById("last_name")?.value || "";
    return `${firstName} ${lastName}`.trim();
}

function getUserPhone() {
    // Get user phone from existing field if available
    return document.getElementById("phone")?.value || "";
}

function updateAddressLabelStyles() {
    const labels = document.querySelectorAll(
        'label:has(input[name="address_label"])'
    );

    labels.forEach((label) => {
        const input = label.querySelector('input[name="address_label"]');
        if (input && input.checked) {
            label.classList.add("border-orange-500", "bg-orange-50");
            label.classList.remove("border-gray-300");
        } else {
            label.classList.remove("border-orange-500", "bg-orange-50");
            label.classList.add("border-gray-300");
        }
    });
}

function validateStep2Complete() {
    console.log("🔍 Validating Step 2...");

    // Remove existing error messages
    const existingError = document.getElementById("step2-errors");
    if (existingError) {
        existingError.remove();
    }

    let isValid = true;
    const errors = [];

    // Clear previous error styling
    document.querySelectorAll(".border-red-500").forEach((el) => {
        el.classList.remove("border-red-500");
    });

    // Check if using saved address or new address
    const savedAddressInput = document.querySelector(
        'input[name="saved_address_id"]:checked'
    );
    const isUsingSavedAddress =
        savedAddressInput && savedAddressInput.value !== "new";

    console.log(
        "Address mode:",
        isUsingSavedAddress ? "Saved Address" : "New Address"
    );

    // Validate required fields regardless of mode
    const requiredFields = [
        { id: "recipient_name", name: "Recipient Name", visible: true },
        { id: "phone_recipient", name: "Recipient Phone", visible: true },
        { id: "street_address", name: "Street Address", visible: true },
        { id: "province_name", name: "Province", visible: false },
        { id: "city_name", name: "City", visible: false },
        { id: "subdistrict_name", name: "Subdistrict", visible: false },
        { id: "postal_code", name: "Postal Code", visible: false },
    ];

    requiredFields.forEach((field) => {
        const element = document.getElementById(field.id);
        const value = element ? element.value.trim() : "";

        console.log(`Field ${field.id}:`, value || "EMPTY");

        if (!value) {
            errors.push(`${field.name} is required`);
            isValid = false;

            // Add error styling for visible fields
            if (element && field.visible) {
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

    // Address label validation (only for new addresses)
    if (!isUsingSavedAddress) {
        const addressLabel = document.querySelector(
            'input[name="address_label"]:checked'
        );
        if (!addressLabel) {
            errors.push("Please select address label (Kantor or Rumah)");
            isValid = false;
        }
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
        }
    }

    // Check if location is properly selected
    const selectedLocationDiv = document.getElementById("selected-location");
    const hasSelectedLocation =
        selectedLocationDiv &&
        !selectedLocationDiv.classList.contains("hidden");

    if (!hasSelectedLocation) {
        // Check if we have province and city data (might be from saved address)
        const provinceValue = document.getElementById("province_name")?.value;
        const cityValue = document.getElementById("city_name")?.value;

        if (!provinceValue || !cityValue) {
            errors.push("Please select a delivery location");
            isValid = false;
        }
    }

    console.log("Validation result:", { isValid, errors });

    if (!isValid) {
        showValidationErrors(errors);
        return false;
    }

    // Fill legacy fields for backward compatibility
    fillLegacyFieldsForCheckout();

    console.log("✅ Step 2 validation passed");
    return true;
}

function fillLegacyFieldsForCheckout() {
    console.log("🔧 Filling legacy fields...");

    const streetAddress =
        document.getElementById("street_address")?.value || "";
    const provinceName = document.getElementById("province_name")?.value || "";
    const cityName = document.getElementById("city_name")?.value || "";
    const subdistrictName =
        document.getElementById("subdistrict_name")?.value || "";
    const postalCode = document.getElementById("postal_code")?.value || "";

    // Create full address string
    const fullAddress = `${streetAddress}, ${subdistrictName}, ${cityName}, ${provinceName} ${postalCode}`;
    const locationString = `${subdistrictName}, ${cityName}, ${provinceName}`;

    // Fill legacy fields
    setFieldValue("legacy_address", fullAddress);
    setFieldValue("legacy_destination_label", locationString);

    console.log("Legacy fields filled:", {
        legacy_address: fullAddress,
        legacy_destination_label: locationString,
    });
}

function showValidationErrors(errors) {
    if (errors.length === 0) return;

    console.log("❌ Showing validation errors:", errors);

    // Create error display
    const errorDiv = document.createElement("div");
    errorDiv.id = "step2-errors";
    errorDiv.className =
        "bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4";

    const errorTitle = document.createElement("p");
    errorTitle.className = "font-medium mb-2";
    errorTitle.textContent = "Please fix the following errors:";
    errorDiv.appendChild(errorTitle);

    const errorList = document.createElement("ul");
    errorList.className = "list-disc list-inside space-y-1 text-sm";

    errors.forEach((error) => {
        const li = document.createElement("li");
        li.textContent = error;
        errorList.appendChild(li);
    });

    errorDiv.appendChild(errorList);

    // Insert error before the buttons
    const step2Section = document.getElementById("section-2");
    const buttonsDiv = step2Section.querySelector(".flex.space-x-4");
    if (buttonsDiv) {
        buttonsDiv.parentNode.insertBefore(errorDiv, buttonsDiv);

        // Scroll to error
        errorDiv.scrollIntoView({ behavior: "smooth", block: "center" });
    }
}

function nextStepWithValidation(stepNumber) {
    console.log(`🚀 Moving to step ${stepNumber}`);

    // Hide all sections
    document.querySelectorAll(".checkout-section").forEach((section) => {
        section.classList.add("hidden");
        section.classList.remove("active");
    });

    // Show target section
    const targetSection = document.getElementById(`section-${stepNumber}`);
    if (targetSection) {
        targetSection.classList.remove("hidden");
        targetSection.classList.add("active");
    }

    // Update step indicators
    updateStepIndicators(stepNumber);

    // Special handling for step 3 (shipping calculation)
    if (stepNumber === 3) {
        calculateShippingForStep3();
    }
}

function updateStepIndicators(activeStep) {
    document.querySelectorAll(".step").forEach((step, index) => {
        const stepNum = index + 1;
        step.classList.remove("active", "completed");

        if (stepNum === activeStep) {
            step.classList.add("active");
        } else if (stepNum < activeStep) {
            step.classList.add("completed");
        }
    });
}

function calculateShippingForStep3() {
    console.log("🚚 Calculating shipping for step 3");

    // Get destination data
    const destinationId = document.getElementById("destination_id")?.value;
    const provinceName = document.getElementById("province_name")?.value;
    const cityName = document.getElementById("city_name")?.value;
    const subdistrictName = document.getElementById("subdistrict_name")?.value;

    console.log("Shipping calculation data:", {
        destinationId,
        provinceName,
        cityName,
        subdistrictName,
    });

    if (!destinationId && !cityName) {
        console.warn("No destination data available for shipping calculation");
        displayShippingError(
            "No destination data available. Please go back and select your location."
        );
        return;
    }

    // Show loading state
    const shippingOptions = document.getElementById("shipping-options");
    const shippingLoading = document.getElementById("shipping-loading");

    if (shippingLoading) {
        shippingLoading.classList.remove("hidden");
    }

    if (shippingOptions) {
        shippingOptions.innerHTML = "";
    }

    // Prepare shipping calculation data
    const shippingData = {
        destination_id: destinationId,
        destination_label: `${subdistrictName}, ${cityName}, ${provinceName}`,
        weight:
            document.querySelector('meta[name="total-weight"]')?.content ||
            1000,
    };

    // Call shipping calculation API - use the existing endpoint
    fetch("/checkout/shipping", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')
                .content,
            Accept: "application/json",
        },
        body: JSON.stringify(shippingData),
    })
        .then((response) => response.json())
        .then((data) => {
            if (shippingLoading) {
                shippingLoading.classList.add("hidden");
            }

            console.log("Shipping calculation response:", data);

            if (data.success && data.options) {
                displayShippingOptions(data.options);
            } else {
                displayShippingError(
                    data.message || "Failed to calculate shipping costs"
                );
            }
        })
        .catch((error) => {
            console.error("Shipping calculation error:", error);
            if (shippingLoading) {
                shippingLoading.classList.add("hidden");
            }
            displayShippingError(
                "Failed to calculate shipping costs. Please try again."
            );
        });
}

function displayShippingOptions(options) {
    const shippingOptions = document.getElementById("shipping-options");
    if (!shippingOptions) return;

    shippingOptions.innerHTML = "";

    if (options.length === 0) {
        shippingOptions.innerHTML = `
            <div class="p-4 text-center text-gray-500 border-2 border-dashed border-gray-200 rounded-lg">
                <p>🚫 No shipping options available for this location</p>
                <p class="text-sm mt-1">Please check your address or contact support</p>
            </div>
        `;
        return;
    }

    options.forEach((option, index) => {
        const optionDiv = document.createElement("div");
        optionDiv.className = "shipping-option";
        optionDiv.innerHTML = `
            <label class="flex items-center p-4 border-2 rounded-lg cursor-pointer hover:bg-gray-50 transition-colors ${
                index === 0 ? "border-blue-500 bg-blue-50" : "border-gray-200"
            }">
                <input type="radio" 
                       name="shipping_option" 
                       value="${
                           option.service ||
                           option.courier + "-" + option.service
                       }" 
                       data-cost="${option.cost}"
                       data-etd="${option.etd}"
                       data-courier="${option.courier || option.courier_name}"
                       ${index === 0 ? "checked" : ""}
                       onchange="selectShippingOption(this)"
                       class="mr-4">
                <div class="flex-1">
                    <div class="flex justify-between items-center">
                        <div>
                            <div class="font-medium text-gray-900">${
                                option.courier_name || option.courier
                            } - ${option.service_name || option.service}</div>
                            <div class="text-sm text-gray-600">${
                                option.description || ""
                            }</div>
                            <div class="text-sm text-blue-600">Estimated: ${
                                option.etd
                            } days</div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-lg text-gray-900">Rp ${formatNumber(
                                option.cost
                            )}</div>
                        </div>
                    </div>
                </div>
            </label>
        `;
        shippingOptions.appendChild(optionDiv);
    });

    // Auto-select first option
    if (options.length > 0) {
        const firstOption = shippingOptions.querySelector(
            'input[name="shipping_option"]'
        );
        if (firstOption) {
            selectShippingOption(firstOption);
        }
    }
}

function selectShippingOption(input) {
    if (!input) return;

    console.log("Shipping option selected:", input.value);

    // Update hidden form fields
    const shippingMethodInput = document.getElementById("shipping_method");
    const shippingCostInput = document.getElementById("shipping_cost");

    if (shippingMethodInput) {
        shippingMethodInput.value = `${input.dataset.courier} - ${input.value} (${input.dataset.etd} days)`;
    }

    if (shippingCostInput) {
        shippingCostInput.value = input.dataset.cost;
    }

    // Update visual selection
    document.querySelectorAll(".shipping-option label").forEach((label) => {
        label.classList.remove("border-blue-500", "bg-blue-50");
        label.classList.add("border-gray-200");
    });

    const selectedLabel = input.closest("label");
    if (selectedLabel) {
        selectedLabel.classList.add("border-blue-500", "bg-blue-50");
        selectedLabel.classList.remove("border-gray-200");
    }

    // Update order summary
    updateOrderSummary();
}

function updateOrderSummary() {
    const shippingCost = document.getElementById("shipping_cost")?.value || 0;
    const subtotal = parseFloat(
        document.querySelector('meta[name="cart-subtotal"]')?.content || 0
    );
    const tax = subtotal * 0.11; // 11% PPN

    // Update shipping cost display
    const shippingCostDisplay = document.getElementById(
        "shipping-cost-display"
    );
    if (shippingCostDisplay) {
        shippingCostDisplay.textContent =
            shippingCost > 0
                ? `Rp ${formatNumber(shippingCost)}`
                : "To be calculated";
    }

    // Update total
    const total = subtotal + parseFloat(shippingCost) + tax;
    const totalDisplay = document.getElementById("total-display");
    if (totalDisplay) {
        totalDisplay.textContent = `Rp ${formatNumber(total)}`;
    }
}

function displayShippingError(message) {
    const shippingOptions = document.getElementById("shipping-options");
    if (!shippingOptions) return;

    shippingOptions.innerHTML = `
        <div class="p-4 text-center text-red-600 border-2 border-red-200 bg-red-50 rounded-lg">
            <p>❌ ${message}</p>
            <button type="button" onclick="calculateShippingForStep3()" 
                    class="mt-2 text-sm bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">
                Try Again
            </button>
        </div>
    `;
}

function formatNumber(num) {
    return new Intl.NumberFormat("id-ID").format(num);
}

function clearLocation() {
    console.log("🧹 Clearing location");

    // Clear all location-related fields
    setFieldValue("province_name", "");
    setFieldValue("city_name", "");
    setFieldValue("subdistrict_name", "");
    setFieldValue("postal_code", "");
    setFieldValue("destination_id", "");
    setFieldValue("legacy_address", "");
    setFieldValue("legacy_destination_label", "");

    // Hide selected location
    const selectedLocation = document.getElementById("selected-location");
    if (selectedLocation) {
        selectedLocation.classList.add("hidden");
    }

    // Focus back to search
    const locationSearch = document.getElementById("location_search");
    if (locationSearch) {
        locationSearch.focus();
    }
}

// Global functions that need to be accessible from HTML
window.loadSavedAddress = loadSavedAddress;
window.showNewAddressForm = showNewAddressForm;
window.updateAddressLabelStyles = updateAddressLabelStyles;
window.clearLocation = clearLocation;
window.selectShippingOption = selectShippingOption;
window.calculateShippingForStep3 = calculateShippingForStep3;

// Navigation functions
window.nextStep = function (stepNumber) {
    if (stepNumber === 3) {
        // Special validation for step 2 -> 3
        if (validateStep2Complete()) {
            nextStepWithValidation(stepNumber);
        }
    } else {
        nextStepWithValidation(stepNumber);
    }
};

window.prevStep = function (stepNumber) {
    nextStepWithValidation(stepNumber);
};

console.log("✅ Checkout address integration loaded successfully");
