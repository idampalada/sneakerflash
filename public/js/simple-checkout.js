// Enhanced Checkout JavaScript with Address Integration
// UPDATED: Fixed shipping calculation and address handling
// File: public/js/enhanced-checkout.js

// Global variables
let subtotal = 0;
let totalWeight = 1000;
let taxRate = 0.11; // 11% PPN
let currentStep = 1;
let availableShippingOptions = [];
let isCalculatingShipping = false;
let searchTimeout;
let selectedDestination = null;
let isSubmittingOrder = false;
let userHasPrimaryAddress = false;
let primaryAddressId = null;

// Initialize checkout when page loads
document.addEventListener("DOMContentLoaded", function () {
    console.log("🚀 Enhanced Checkout with Address Integration initialized");

    // Initialize variables from data attributes
    initializeVariables();

    console.log("📦 Total weight:", totalWeight, "grams");
    console.log("💰 Subtotal:", subtotal);
    console.log("🏠 User has primary address:", userHasPrimaryAddress);

    // Setup event listeners
    setupEventListeners();

    // Test connection
    testRajaOngkirConnection();

    // Initialize password fields if create account was checked
    if (document.getElementById("create_account")?.checked) {
        togglePassword();
    }

    // Initialize payment method handlers
    initializePaymentMethods();

    // Load Midtrans Snap script
    loadMidtransScript();

    // Initialize address integration
    initializeAddressIntegration();
});

function initializeVariables() {
    // Get values from meta tags and form elements
    const subtotalMeta = document.querySelector('meta[name="cart-subtotal"]');
    const weightMeta = document.querySelector('meta[name="total-weight"]');
    const userAuthMeta = document.querySelector(
        'meta[name="user-authenticated"]'
    );
    const hasPrimaryMeta = document.querySelector(
        'meta[name="user-has-primary-address"]'
    );
    const primaryIdMeta = document.querySelector(
        'meta[name="primary-address-id"]'
    );

    if (subtotalMeta) {
        subtotal = parseInt(subtotalMeta.content) || 0;
    }

    if (weightMeta) {
        totalWeight = parseInt(weightMeta.content) || 1000;
    }

    if (userAuthMeta && userAuthMeta.content === "true") {
        userHasPrimaryAddress =
            hasPrimaryMeta && hasPrimaryMeta.content === "true";
        primaryAddressId =
            primaryIdMeta && primaryIdMeta.content !== "null"
                ? primaryIdMeta.content
                : null;
    }

    console.log("Variables initialized:", {
        subtotal,
        totalWeight,
        userHasPrimaryAddress,
        primaryAddressId,
    });
}

function initializeAddressIntegration() {
    console.log("🏠 Initializing address integration");

    // Setup address label selection
    setupAddressLabelSelection();

    // Setup location search
    setupLocationSearch();

    // Setup saved address selection
    setupSavedAddressSelection();

    // Auto-load primary address if available
    if (userHasPrimaryAddress && primaryAddressId) {
        console.log("🔄 Auto-loading primary address:", primaryAddressId);
        loadSavedAddress(primaryAddressId);
    }
}

function setupAddressLabelSelection() {
    const addressLabelInputs = document.querySelectorAll(
        'input[name="address_label"]'
    );

    addressLabelInputs.forEach((input) => {
        input.addEventListener("change", updateAddressLabelStyles);
    });

    // Set default to "Rumah" if none selected
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

function setupLocationSearch() {
    const locationSearch = document.getElementById("location_search");
    const locationResults = document.getElementById("location-results");

    if (!locationSearch || !locationResults) return;

    locationSearch.addEventListener("input", function () {
        const query = this.value.trim();

        clearTimeout(searchTimeout);

        if (query.length < 2) {
            locationResults.classList.add("hidden");
            return;
        }

        searchTimeout = setTimeout(() => {
            searchLocation(query);
        }, 300);
    });

    // Hide results when clicking outside
    document.addEventListener("click", function (e) {
        if (
            !locationSearch.contains(e.target) &&
            !locationResults.contains(e.target)
        ) {
            locationResults.classList.add("hidden");
        }
    });
}

async function searchLocation(query) {
    const locationResults = document.getElementById("location-results");
    if (!locationResults) return;

    // Show loading
    locationResults.innerHTML =
        '<div class="p-3 text-center"><div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600 mx-auto"></div><span class="text-sm text-gray-600 ml-2">Searching...</span></div>';
    locationResults.classList.remove("hidden");

    try {
        const response = await fetch(
            "/checkout/search-destinations?search=" +
                encodeURIComponent(query) +
                "&limit=10",
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
            locationResults.innerHTML =
                '<div class="p-3 text-center text-red-500">Search failed. Please try again.</div>';
        }
    } catch (error) {
        console.error("Location search error:", error);
        locationResults.innerHTML =
            '<div class="p-3 text-center text-red-500">Search failed. Please try again.</div>';
    }
}

function displayLocationResults(locations) {
    const locationResults = document.getElementById("location-results");

    if (locations.length === 0) {
        locationResults.innerHTML =
            '<div class="p-3 text-center text-gray-500">No locations found</div>';
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
    console.log("📍 Location selected:", location);

    // Fill hidden fields
    document.getElementById("province_name").value =
        location.province_name || "";
    document.getElementById("city_name").value = location.city_name || "";
    document.getElementById("subdistrict_name").value =
        location.subdistrict_name || "";
    document.getElementById("postal_code").value =
        location.zip_code || location.postal_code || "";
    document.getElementById("destination_id").value =
        location.location_id || location.destination_id || "";

    // Fill legacy fields for backward compatibility
    const legacyAddress = document.getElementById("legacy_address");
    const legacyDestinationLabel = document.getElementById(
        "legacy_destination_label"
    );

    if (legacyAddress)
        legacyAddress.value = location.full_address || location.label || "";
    if (legacyDestinationLabel)
        legacyDestinationLabel.value =
            location.full_address || location.label || "";

    // Update selectedDestination for shipping calculation
    selectedDestination = location;

    // Display selected location
    const selectedLocation = document.getElementById("selected-location");
    const selectedLocationText = document.getElementById(
        "selected-location-text"
    );

    if (selectedLocation && selectedLocationText) {
        selectedLocationText.textContent =
            location.full_address ||
            location.label ||
            location.subdistrict_name +
                ", " +
                location.city_name +
                ", " +
                location.province_name;
        selectedLocation.classList.remove("hidden");
    }

    // Hide search results
    document.getElementById("location-results").classList.add("hidden");

    // Clear search input
    document.getElementById("location_search").value = "";

    // Trigger shipping calculation if we're on step 3
    if (currentStep >= 3) {
        setTimeout(() => calculateShipping(), 500);
    }
}

function setupSavedAddressSelection() {
    const savedAddressInputs = document.querySelectorAll(
        'input[name="saved_address_id"]'
    );

    savedAddressInputs.forEach((input) => {
        input.addEventListener("change", function () {
            // Update selection styles
            document
                .querySelectorAll("label[data-address-id]")
                .forEach((label) => {
                    label.classList.remove("border-orange-500", "bg-orange-50");
                    label.classList.add("border-gray-200");
                });

            const selectedLabel = this.closest("label");
            if (selectedLabel && this.value !== "new") {
                selectedLabel.classList.add(
                    "border-orange-500",
                    "bg-orange-50"
                );
                selectedLabel.classList.remove("border-gray-200");
            }

            // Load address data
            if (this.value === "new") {
                showNewAddressForm();
            } else {
                loadSavedAddress(this.value);
            }
        });
    });
}

function loadSavedAddress(addressId) {
    console.log("🔄 Loading saved address:", addressId);

    if (addressId === "new") {
        showNewAddressForm();
        return;
    }

    // Hide new address form
    const newAddressForm = document.getElementById("new-address-form");
    if (newAddressForm) {
        newAddressForm.classList.add("hidden");
    }

    // Fetch address data
    fetch("/profile/addresses/" + addressId + "/show", {
        headers: {
            Accept: "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')
                .content,
        },
    })
        .then((response) => response.json())
        .then((data) => {
            if (data.success) {
                populateAddressForm(data.address);
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

function populateAddressForm(address) {
    console.log("📝 Populating address form:", address);

    // Fill form fields
    document.getElementById("recipient_name").value = address.recipient_name;
    document.getElementById("phone_recipient").value = address.phone_recipient;
    document.getElementById("street_address").value = address.street_address;

    // Fill location fields
    document.getElementById("province_name").value = address.province_name;
    document.getElementById("city_name").value = address.city_name;
    document.getElementById("subdistrict_name").value =
        address.subdistrict_name;
    document.getElementById("postal_code").value = address.postal_code;
    document.getElementById("destination_id").value =
        address.destination_id || "";

    // Fill legacy fields
    const legacyAddress = document.getElementById("legacy_address");
    const legacyDestinationLabel = document.getElementById(
        "legacy_destination_label"
    );

    if (legacyAddress) legacyAddress.value = address.full_address;
    if (legacyDestinationLabel)
        legacyDestinationLabel.value = address.location_string;

    // Set selectedDestination for shipping calculation
    selectedDestination = {
        location_id: address.destination_id,
        label: address.location_string,
        full_address: address.full_address,
    };

    // Show selected location
    const selectedLocation = document.getElementById("selected-location");
    const selectedLocationText = document.getElementById(
        "selected-location-text"
    );

    if (selectedLocation && selectedLocationText) {
        selectedLocationText.textContent = address.location_string;
        selectedLocation.classList.remove("hidden");
    }

    // Set address label
    const labelInput = document.querySelector(
        'input[name="address_label"][value="' + address.label + '"]'
    );
    if (labelInput) {
        labelInput.checked = true;
        updateAddressLabelStyles();
    }

    // Disable save options since this is existing address
    const saveCheckbox = document.querySelector('input[name="save_address"]');
    const primaryCheckbox = document.querySelector(
        'input[name="set_as_primary"]'
    );
    if (saveCheckbox) saveCheckbox.checked = false;
    if (primaryCheckbox) primaryCheckbox.checked = false;
}

function showNewAddressForm() {
    console.log("📝 Showing new address form");

    const newAddressForm = document.getElementById("new-address-form");
    if (newAddressForm) {
        newAddressForm.classList.remove("hidden");
    }

    // Get user data from meta tags
    const authenticatedUserName = document.querySelector(
        'meta[name="authenticated-user-name"]'
    ).content;
    const authenticatedUserPhone = document.querySelector(
        'meta[name="authenticated-user-phone"]'
    ).content;

    // Pre-fill with user data
    document.getElementById("recipient_name").value = authenticatedUserName;
    document.getElementById("phone_recipient").value = authenticatedUserPhone;
    document.getElementById("street_address").value = "";

    // Clear location fields
    document.getElementById("province_name").value = "";
    document.getElementById("city_name").value = "";
    document.getElementById("subdistrict_name").value = "";
    document.getElementById("postal_code").value = "";
    document.getElementById("destination_id").value = "";

    // Clear legacy fields
    const legacyAddress = document.getElementById("legacy_address");
    const legacyDestinationLabel = document.getElementById(
        "legacy_destination_label"
    );
    if (legacyAddress) legacyAddress.value = "";
    if (legacyDestinationLabel) legacyDestinationLabel.value = "";

    // Hide selected location
    const selectedLocation = document.getElementById("selected-location");
    if (selectedLocation) {
        selectedLocation.classList.add("hidden");
    }

    // Reset selectedDestination
    selectedDestination = null;

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
    if (saveCheckbox) saveCheckbox.checked = true;
}

function clearLocation() {
    console.log("🗑️ Clearing location");

    // Clear fields
    document.getElementById("province_name").value = "";
    document.getElementById("city_name").value = "";
    document.getElementById("subdistrict_name").value = "";
    document.getElementById("postal_code").value = "";
    document.getElementById("destination_id").value = "";

    // Clear legacy fields
    const legacyAddress = document.getElementById("legacy_address");
    const legacyDestinationLabel = document.getElementById(
        "legacy_destination_label"
    );
    if (legacyAddress) legacyAddress.value = "";
    if (legacyDestinationLabel) legacyDestinationLabel.value = "";

    // Hide selected location
    const selectedLocation = document.getElementById("selected-location");
    if (selectedLocation) {
        selectedLocation.classList.add("hidden");
    }

    // Reset selectedDestination
    selectedDestination = null;

    // Focus back to search
    const locationSearch = document.getElementById("location_search");
    if (locationSearch) {
        locationSearch.focus();
    }
}

function setupEventListeners() {
    // Enhanced form submission handling
    const checkoutForm = document.getElementById("checkout-form");
    if (checkoutForm) {
        checkoutForm.addEventListener("submit", function (e) {
            e.preventDefault();

            if (isSubmittingOrder) {
                console.log("⏳ Order submission already in progress");
                return false;
            }

            // Validate current step
            if (!validateCurrentStep()) {
                return false;
            }

            // Check payment method
            const paymentMethod = document.querySelector(
                'input[name="payment_method"]:checked'
            )?.value;
            if (!paymentMethod) {
                alert("Please select a payment method.");
                return false;
            }

            handleOrderSubmission(paymentMethod);
        });
    }
}

function initializePaymentMethods() {
    const paymentMethods = document.querySelectorAll(
        'input[name="payment_method"]'
    );
    paymentMethods.forEach((method) => {
        method.addEventListener("change", function () {
            updateSubmitButtonText(this.value);
        });
    });
}

function updateSubmitButtonText(paymentMethod) {
    const submitBtn = document.getElementById("place-order-btn");
    if (!submitBtn) return;

    switch (paymentMethod) {
        case "cod":
            submitBtn.textContent = "Place Order (COD)";
            submitBtn.className =
                "flex-1 bg-green-600 text-white py-3 rounded-lg hover:bg-green-700 transition-colors font-medium";
            break;
        case "bank_transfer":
        case "credit_card":
        case "ewallet":
            submitBtn.textContent = "Continue to Payment";
            submitBtn.className =
                "flex-1 bg-blue-600 text-white py-3 rounded-lg hover:bg-blue-700 transition-colors font-medium";
            break;
        default:
            submitBtn.textContent = "Place Order";
            submitBtn.className =
                "flex-1 bg-green-600 text-white py-3 rounded-lg hover:bg-green-700 transition-colors font-medium";
    }
}

// ENHANCED: Handle order submission with address integration
function handleOrderSubmission(paymentMethod) {
    console.log("🛒 Processing order with payment method:", paymentMethod);

    if (isSubmittingOrder) {
        console.log("⏳ Order submission already in progress");
        return false;
    }

    isSubmittingOrder = true;
    const submitBtn = document.getElementById("place-order-btn");

    // Update button state
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = `
            <div class="flex items-center justify-center">
                <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-white mr-2"></div>
                Processing Order...
            </div>
        `;
    }

    showProcessingMessage(paymentMethod);

    // Get form data
    const form = document.getElementById("checkout-form");
    const formData = new FormData(form);

    // ENHANCED: Ensure all address fields are properly filled
    validateAndFillAddressFields(formData);

    // Debug form data
    console.log("📋 Form data being sent:");
    for (let [key, value] of formData.entries()) {
        console.log(`${key}: ${value}`);
    }

    // Ensure privacy_accepted is set correctly
    const privacyCheckbox = document.getElementById("privacy_accepted");
    if (privacyCheckbox && privacyCheckbox.checked) {
        formData.set("privacy_accepted", "1");
        console.log("✅ Privacy accepted: true");
    } else {
        console.log("❌ Privacy not accepted");
        resetSubmitButton();
        alert("Please accept the privacy policy to continue.");
        isSubmittingOrder = false;
        return;
    }

    // Get CSRF token
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");
    console.log("🔑 CSRF Token:", csrfToken ? "Found" : "Not found");

    // Submit with enhanced error handling
    fetch("/checkout", {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": csrfToken,
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest",
            "Content-Type": "application/x-www-form-urlencoded",
        },
        body: new URLSearchParams(formData),
    })
        .then(async (response) => {
            console.log("📤 Response status:", response.status);

            const contentType = response.headers.get("content-type");
            console.log("📄 Content type:", contentType);

            if (contentType && contentType.includes("application/json")) {
                const data = await response.json();
                console.log("✅ JSON Response received:", data);
                return { success: true, data: data, status: response.status };
            } else {
                const text = await response.text();
                console.log(
                    "❌ Non-JSON response received:",
                    text.substring(0, 500)
                );

                try {
                    const data = JSON.parse(text);
                    return {
                        success: true,
                        data: data,
                        status: response.status,
                    };
                } catch (e) {
                    return {
                        success: false,
                        error: "Server returned non-JSON response",
                        status: response.status,
                        text: text,
                    };
                }
            }
        })
        .then((result) => {
            if (result.success && result.data) {
                const data = result.data;

                if (data.success) {
                    console.log("🎉 Order successful:", data);
                    handleSuccessfulOrder(data, paymentMethod);
                } else if (data.errors) {
                    console.log("❌ Validation errors:", data.errors);
                    handleOrderErrors(data.errors);
                } else if (data.error) {
                    console.log("❌ Order error:", data.error);
                    handleOrderError(data.error);
                } else {
                    console.log("❓ Unexpected response format:", data);
                    handleOrderError("Unexpected response format from server");
                }
            } else {
                console.log("❌ Request failed:", result);
                handleOrderError(result.error || "Server error occurred");
            }
        })
        .catch((error) => {
            console.error("❌ Network error:", error);
            handleOrderError(
                "Failed to connect to server. Please check your internet connection and try again."
            );
        })
        .finally(() => {
            console.log("🏁 Request completed");
            isSubmittingOrder = false;
            resetSubmitButton();
        });
}

// NEW: Validate and fill address fields before submission
function validateAndFillAddressFields(formData) {
    // Ensure all required address fields are filled
    const requiredAddressFields = [
        "recipient_name",
        "phone_recipient",
        "province_name",
        "city_name",
        "subdistrict_name",
        "postal_code",
        "street_address",
    ];

    requiredAddressFields.forEach((field) => {
        const element = document.getElementById(field);
        if (element && element.value) {
            formData.set(field, element.value);
        }
    });

    // Ensure destination_id is set
    const destinationIdElement = document.getElementById("destination_id");
    if (destinationIdElement && destinationIdElement.value) {
        formData.set("destination_id", destinationIdElement.value);
    }

    // Set address label if not set
    if (!formData.get("address_label")) {
        const addressLabelInput = document.querySelector(
            'input[name="address_label"]:checked'
        );
        if (addressLabelInput) {
            formData.set("address_label", addressLabelInput.value);
        } else {
            formData.set("address_label", "Rumah"); // Default
        }
    }

    // Fill legacy address field for backward compatibility
    const streetAddress = formData.get("street_address");
    const locationString = `${formData.get("subdistrict_name")}, ${formData.get(
        "city_name"
    )}, ${formData.get("province_name")} ${formData.get("postal_code")}`;

    if (streetAddress) {
        const fullAddress = `${streetAddress}, ${locationString}`;
        formData.set("address", fullAddress);
        formData.set("destination_label", locationString);
    }
}

function handleSuccessfulOrder(data, paymentMethod) {
    console.log("🎯 Handling successful order:", data);

    if (paymentMethod === "cod") {
        console.log("🚚 COD order, redirect to success");

        if (data.redirect_url) {
            window.location.href = data.redirect_url;
        } else if (data.order_number) {
            window.location.href = `/checkout/success/${data.order_number}`;
        } else {
            showSuccess("✅ COD order successfully created!");
            setTimeout(() => (window.location.href = "/"), 2000);
        }
    } else {
        console.log("💳 Online payment, handle Midtrans");

        if (data.snap_token) {
            console.log("💳 Snap token received, open Midtrans popup");
            showSuccess("💳 Opening payment gateway...");

            setTimeout(() => {
                openMidtransPayment(data.snap_token, data.order_number);
            }, 1000);
        } else if (data.redirect_url) {
            console.log("🔄 Redirect to payment page:", data.redirect_url);
            window.location.href = data.redirect_url;
        } else if (data.order_number) {
            console.log("🔄 Redirect to payment page with order number");
            window.location.href = `/checkout/payment/${data.order_number}`;
        } else {
            console.error("❌ No snap token or redirect URL");
            handleOrderError(
                "Failed to create payment session. Please contact support."
            );
        }
    }
}

function openMidtransPayment(snapToken, orderNumber) {
    console.log("💳 Opening Midtrans payment with token:", snapToken);

    if (typeof window.snap === "undefined") {
        console.error("❌ Midtrans Snap not loaded");

        loadMidtransScript()
            .then(() => {
                console.log("✅ Midtrans script loaded, retry payment");
                setTimeout(
                    () => openMidtransPayment(snapToken, orderNumber),
                    1000
                );
            })
            .catch(() => {
                handleOrderError(
                    "Payment system not available. Please refresh the page."
                );
            });
        return;
    }

    showSuccess("💳 Opening payment gateway...");

    try {
        window.snap.pay(snapToken, {
            onSuccess: function (result) {
                console.log("✅ Payment successful:", result);
                showSuccess("✅ Payment successful! Redirecting...");

                setTimeout(() => {
                    if (result.order_id) {
                        window.location.href = `/checkout/success/${result.order_id}?payment=success`;
                    } else {
                        window.location.href = `/checkout/success/${orderNumber}?payment=success`;
                    }
                }, 1500);
            },

            onPending: function (result) {
                console.log("⏳ Payment pending:", result);
                showWarning(
                    "⏳ Payment is being processed. You will receive confirmation shortly."
                );

                setTimeout(() => {
                    if (result.order_id) {
                        window.location.href = `/checkout/success/${result.order_id}?payment=pending`;
                    } else {
                        window.location.href = `/checkout/success/${orderNumber}?payment=pending`;
                    }
                }, 2000);
            },

            onError: function (result) {
                console.error("❌ Payment error:", result);
                handleOrderError(
                    "Payment failed. Please try again or use a different payment method."
                );
            },

            onClose: function () {
                console.log("🔒 Payment popup closed by user");
                showWarning(
                    "Payment was cancelled. You can continue payment later from your order page."
                );

                setTimeout(() => {
                    if (
                        confirm(
                            "Would you like to view your order and try payment again?"
                        )
                    ) {
                        window.location.href = `/checkout/success/${orderNumber}`;
                    } else {
                        resetSubmitButton();
                    }
                }, 1000);
            },
        });
    } catch (error) {
        console.error("❌ Error opening Midtrans:", error);
        handleOrderError("Failed to open payment gateway. Please try again.");
    }
}

function loadMidtransScript() {
    return new Promise((resolve, reject) => {
        if (window.snap) {
            resolve();
            return;
        }

        const clientKey = document
            .querySelector('meta[name="midtrans-client-key"]')
            ?.getAttribute("content");
        const isProduction =
            document
                .querySelector('meta[name="midtrans-production"]')
                ?.getAttribute("content") === "true";

        if (!clientKey) {
            console.error("⚠️ Midtrans client key not found");
            reject(new Error("Midtrans client key not found"));
            return;
        }

        if (document.querySelector("script[data-client-key]")) {
            setTimeout(() => {
                if (window.snap) {
                    resolve();
                } else {
                    reject(
                        new Error(
                            "Midtrans script loaded but snap not available"
                        )
                    );
                }
            }, 1000);
            return;
        }

        const script = document.createElement("script");
        script.src = isProduction
            ? "https://app.midtrans.com/snap/snap.js"
            : "https://app.sandbox.midtrans.com/snap/snap.js";
        script.setAttribute("data-client-key", clientKey);

        script.onload = function () {
            console.log("✅ Midtrans Snap script successfully loaded");
            setTimeout(() => {
                if (window.snap) {
                    resolve();
                } else {
                    reject(
                        new Error("Snap object not available after script load")
                    );
                }
            }, 500);
        };

        script.onerror = function () {
            console.error("❌ Failed to load Midtrans Snap script");
            reject(new Error("Failed to load Midtrans script"));
        };

        document.head.appendChild(script);
    });
}

// ENHANCED: Shipping calculation with proper error handling
async function calculateShipping() {
    if (!selectedDestination) {
        console.log("❌ No destination selected for shipping calculation");
        displayShippingError("Please select your delivery location first");
        return;
    }

    if (isCalculatingShipping) {
        console.log("⏳ Shipping calculation already in progress");
        return;
    }

    console.log("🚚 Calculating shipping to:", selectedDestination);

    isCalculatingShipping = true;

    const shippingOptions = document.getElementById("shipping-options");
    const loadingDiv = document.getElementById("shipping-loading");

    // Show loading
    if (shippingOptions) shippingOptions.classList.add("hidden");
    if (loadingDiv) loadingDiv.classList.remove("hidden");

    const requestData = {
        destination_id:
            selectedDestination.location_id ||
            selectedDestination.destination_id,
        destination_label:
            selectedDestination.label ||
            selectedDestination.full_address ||
            `${selectedDestination.subdistrict_name}, ${selectedDestination.city_name}`,
        weight: totalWeight,
    };

    console.log("📦 Shipping request data:", requestData);

    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");

    try {
        const response = await fetch("/checkout/calculate-shipping", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                Accept: "application/json",
                "X-CSRF-TOKEN": csrfToken,
                "X-Requested-With": "XMLHttpRequest",
            },
            body: JSON.stringify(requestData),
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        const data = await response.json();
        console.log("✅ Shipping calculation response:", data);

        if (data.success && data.options && data.options.length > 0) {
            displayShippingOptions(data.options);
            availableShippingOptions = data.options;
        } else {
            throw new Error(data.error || "No shipping options available");
        }
    } catch (error) {
        console.error("❌ Shipping calculation error:", error);
        displayShippingError(
            error.message || "Failed to calculate shipping options"
        );
    } finally {
        isCalculatingShipping = false;
        if (loadingDiv) loadingDiv.classList.add("hidden");
        if (shippingOptions) shippingOptions.classList.remove("hidden");
    }
}

function displayShippingOptions(options) {
    const shippingOptions = document.getElementById("shipping-options");
    if (!shippingOptions) return;

    let html = "";
    options.forEach((option, index) => {
        const isChecked = index === 0 ? "checked" : "";
        const mockBadge =
            option.is_mock || option.type === "mock"
                ? '<span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded ml-2">Estimate</span>'
                : "";
        const recommendedBadge = option.recommended
            ? '<span class="text-xs bg-green-100 text-green-800 px-2 py-1 rounded ml-2">Recommended</span>'
            : "";

        html += `
            <label class="shipping-option flex items-center p-4 border rounded-lg cursor-pointer hover:bg-gray-50 ${
                isChecked ? "border-blue-500 bg-blue-50" : "border-gray-200"
            }">
                <input type="radio" name="shipping_option" value="${
                    option.courier
                }_${option.service}" 
                       data-cost="${option.cost}" 
                       data-description="${option.courier} ${
            option.service
        } - ${option.description}"
                       onchange="selectShipping(this)" ${isChecked}
                       class="mr-4">
                <div class="shipping-content flex-1">
                    <div class="font-medium flex items-center">
                        ${option.courier_name || option.courier} - ${
            option.service
        }
                        ${recommendedBadge}
                        ${mockBadge}
                    </div>
                    <div class="text-sm text-gray-600">${
                        option.description
                    }</div>
                    <div class="text-sm text-gray-600">Estimated delivery: ${
                        option.formatted_etd || option.etd + " days"
                    }</div>
                </div>
                <div class="font-semibold text-blue-600">${
                    option.formatted_cost
                }</div>
            </label>
        `;
    });

    shippingOptions.innerHTML = html;

    // Auto-select first option
    if (options.length > 0) {
        const firstOption = options[0];
        const shippingMethodEl = document.getElementById("shipping_method");
        const shippingCostEl = document.getElementById("shipping_cost");

        if (shippingMethodEl) {
            shippingMethodEl.value = `${firstOption.courier} ${firstOption.service} - ${firstOption.description}`;
        }
        if (shippingCostEl) {
            shippingCostEl.value = firstOption.cost;
        }
        updateTotals(firstOption.cost);
    }
}

function displayShippingError(errorMessage = "Unable to calculate shipping") {
    const shippingOptions = document.getElementById("shipping-options");
    if (!shippingOptions) return;

    shippingOptions.innerHTML = `
        <div class="p-4 text-center border-2 border-dashed border-red-200 rounded-lg">
            <p class="text-red-600 mb-2">❌ ${errorMessage}</p>
            <p class="text-sm text-gray-600">Please try selecting a different location or contact support.</p>
            <button type="button" onclick="calculateShipping()" 
                    class="mt-2 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                Try Again
            </button>
        </div>
    `;

    resetShippingOptions();
}

function resetShippingOptions() {
    const shippingMethodEl = document.getElementById("shipping_method");
    const shippingCostEl = document.getElementById("shipping_cost");

    if (shippingMethodEl) shippingMethodEl.value = "";
    if (shippingCostEl) shippingCostEl.value = "0";

    updateTotals(0);
    availableShippingOptions = [];
}

function selectShipping(radio) {
    console.log("🚚 Selected shipping:", radio.dataset.description);

    const shippingMethodEl = document.getElementById("shipping_method");
    const shippingCostEl = document.getElementById("shipping_cost");

    if (shippingMethodEl) shippingMethodEl.value = radio.dataset.description;
    if (shippingCostEl) shippingCostEl.value = radio.dataset.cost;

    updateTotals(parseInt(radio.dataset.cost));

    // Update selection styles
    const shippingOptions = document.getElementById("shipping-options");
    if (shippingOptions) {
        shippingOptions
            .querySelectorAll(".shipping-option")
            .forEach((option) => {
                option.classList.remove("border-blue-500", "bg-blue-50");
                option.classList.add("border-gray-200");
            });

        radio
            .closest(".shipping-option")
            .classList.add("border-blue-500", "bg-blue-50");
        radio.closest(".shipping-option").classList.remove("border-gray-200");
    }
}

function updateTotals(shippingCost) {
    const tax = subtotal * taxRate;
    const total = subtotal + shippingCost + tax;

    const shippingDisplay = document.getElementById("shipping-cost-display");
    const taxDisplay = document.getElementById("tax-display");
    const totalDisplay = document.getElementById("total-display");

    if (shippingDisplay) {
        shippingDisplay.textContent =
            shippingCost === 0
                ? "Free"
                : "Rp " + shippingCost.toLocaleString("id-ID");
    }
    if (taxDisplay) {
        taxDisplay.textContent =
            "Rp " + Math.round(tax).toLocaleString("id-ID");
    }
    if (totalDisplay) {
        totalDisplay.textContent =
            "Rp " + Math.round(total).toLocaleString("id-ID");
    }
}

// Step navigation functions with enhanced validation
function nextStep(step) {
    if (validateCurrentStep()) {
        showStep(step);

        // Auto-calculate shipping when reaching step 3
        if (step === 3 && selectedDestination) {
            setTimeout(() => {
                calculateShipping();
            }, 500);
        }
    }
}

function prevStep(step) {
    showStep(step);
}

function showStep(step) {
    // Hide all sections
    document.querySelectorAll(".checkout-section").forEach((section) => {
        section.classList.remove("active");
        section.classList.add("hidden");
    });

    // Reset all step indicators
    document.querySelectorAll(".step").forEach((stepEl) => {
        stepEl.classList.remove("active", "completed");
    });

    // Mark completed steps
    for (let i = 1; i < step; i++) {
        const stepEl = document.getElementById(`step-${i}`);
        if (stepEl) stepEl.classList.add("completed");
    }

    // Show current step
    const currentSection = document.getElementById(`section-${step}`);
    const currentStepEl = document.getElementById(`step-${step}`);

    if (currentSection) {
        currentSection.classList.remove("hidden");
        currentSection.classList.add("active");
    }
    if (currentStepEl) {
        currentStepEl.classList.add("active");
    }

    currentStep = step;

    // Scroll to top of form
    const container = document.querySelector(".container");
    if (container) {
        container.scrollIntoView({ behavior: "smooth" });
    }
}

// ENHANCED: Step validation with address integration
function validateCurrentStep() {
    switch (currentStep) {
        case 1:
            return validateStep1();
        case 2:
            return validateStep2();
        case 3:
            return validateStep3();
        default:
            return true;
    }
}

function validateStep1() {
    const firstName = document.getElementById("first_name")?.value.trim();
    const lastName = document.getElementById("last_name")?.value.trim();
    const email = document.getElementById("email")?.value.trim();
    const phone = document.getElementById("phone")?.value.trim();
    const privacyAccepted =
        document.getElementById("privacy_accepted")?.checked;

    if (!firstName || !lastName || !email || !phone) {
        alert(
            "Please fill in all required fields: First name, Last name, Email, and Phone."
        );
        return false;
    }

    if (!privacyAccepted) {
        alert("Please accept the privacy policy to continue.");
        return false;
    }

    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        alert("Please enter a valid email address.");
        return false;
    }

    const createAccount = document.getElementById("create_account")?.checked;
    if (createAccount) {
        const password = document.getElementById("password")?.value;
        const passwordConfirmation = document.getElementById(
            "password_confirmation"
        )?.value;

        if (!password || password.length < 8) {
            alert("Password must be at least 8 characters long.");
            return false;
        }

        if (password !== passwordConfirmation) {
            alert("Password confirmation does not match.");
            return false;
        }
    }

    return true;
}

function validateStep2() {
    // Check if using saved address
    const savedAddressInput = document.querySelector(
        'input[name="saved_address_id"]:checked'
    );

    if (savedAddressInput && savedAddressInput.value !== "new") {
        // Using saved address - check if it's loaded
        const destinationId = document.getElementById("destination_id")?.value;
        if (!destinationId) {
            alert("Please wait for the selected address to load completely.");
            return false;
        }
        return true;
    }

    // Using new address - validate all fields
    const requiredFields = [
        { id: "recipient_name", name: "Recipient Name" },
        { id: "phone_recipient", name: "Recipient Phone" },
        { id: "street_address", name: "Street Address" },
        { id: "province_name", name: "Province" },
        { id: "city_name", name: "City" },
        { id: "subdistrict_name", name: "Subdistrict" },
        { id: "postal_code", name: "Postal Code" },
    ];

    for (const field of requiredFields) {
        const element = document.getElementById(field.id);
        if (!element || !element.value.trim()) {
            alert(`${field.name} is required.`);
            return false;
        }
    }

    // Check address label
    const addressLabel = document.querySelector(
        'input[name="address_label"]:checked'
    );
    if (!addressLabel) {
        alert("Please select address label (Kantor or Rumah).");
        return false;
    }

    // Validate phone number format
    const phoneInput = document.getElementById("phone_recipient");
    if (phoneInput && phoneInput.value) {
        const phoneRegex = /^[0-9+\-\s\(\)]{10,}$/;
        if (!phoneRegex.test(phoneInput.value)) {
            alert("Please enter a valid phone number (minimum 10 digits).");
            return false;
        }
    }

    // Validate postal code format
    const postalInput = document.getElementById("postal_code");
    if (postalInput && postalInput.value) {
        const postalRegex = /^[0-9]{5}$/;
        if (!postalRegex.test(postalInput.value)) {
            alert("Postal code must be exactly 5 digits.");
            return false;
        }
    }

    return true;
}

function validateStep3() {
    const shippingMethod = document.getElementById("shipping_method")?.value;
    const shippingCost = document.getElementById("shipping_cost")?.value;

    if (!shippingMethod || !shippingCost || shippingCost === "0") {
        alert("Please select a shipping method.");
        return false;
    }

    return true;
}

// Helper functions
function showProcessingMessage(paymentMethod) {
    const statusEl = document.getElementById("connection-status");
    const statusText = document.getElementById("status-text");

    if (!statusEl || !statusText) return;

    let message = "";
    switch (paymentMethod) {
        case "cod":
            message = "🚚 Processing COD order...";
            break;
        case "bank_transfer":
        case "credit_card":
        case "ewallet":
            message = "💳 Creating payment session...";
            break;
        default:
            message = "🛒 Processing your order...";
    }

    statusEl.className =
        "mb-4 p-3 rounded-lg border bg-blue-50 border-blue-200";
    statusText.textContent = message;
    statusEl.classList.remove("hidden");
}

function handleOrderErrors(errors) {
    let errorMessage = "Please fix the following errors:\n";

    if (typeof errors === "object") {
        Object.keys(errors).forEach((field) => {
            if (Array.isArray(errors[field])) {
                errorMessage += `\n• ${errors[field].join(", ")}`;
            } else {
                errorMessage += `\n• ${errors[field]}`;
            }
        });
    } else {
        errorMessage = errors;
    }

    alert(errorMessage);
    showError("❌ Please fix the errors and try again.");
}

function handleOrderError(message) {
    console.error("❌ Order error:", message);
    alert(message);
    showError("❌ " + message);
}

function resetSubmitButton() {
    const submitBtn = document.getElementById("place-order-btn");
    if (!submitBtn) return;

    const paymentMethod = document.querySelector(
        'input[name="payment_method"]:checked'
    )?.value;

    submitBtn.disabled = false;
    updateSubmitButtonText(paymentMethod || "default");
}

function testRajaOngkirConnection() {
    console.log("🔍 Testing RajaOngkir V2 connection...");

    fetch("/checkout/search-destinations?search=jakarta&limit=1", {
        method: "GET",
        headers: {
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest",
        },
    })
        .then((response) => {
            console.log("📡 RajaOngkir test response status:", response.status);
            return response.json();
        })
        .then((data) => {
            if (data.success && data.data && data.data.length > 0) {
                console.log("✅ RajaOngkir V2 connection successful");
                showSuccess("✅ Shipping service connected successfully");
            } else {
                console.log("⚠️ RajaOngkir returned empty data");
                showWarning(
                    "⚠️ Shipping service connected but limited data available"
                );
            }
        })
        .catch((error) => {
            console.error("❌ RajaOngkir connection failed:", error);
            showError(
                "❌ Failed to connect to shipping service. Using fallback options."
            );
        });
}

// Status message functions
function showSuccess(message) {
    const statusEl = document.getElementById("connection-status");
    const statusText = document.getElementById("status-text");
    if (statusEl && statusText) {
        statusEl.className =
            "mb-4 p-3 rounded-lg border bg-green-50 border-green-200";
        statusText.textContent = message;
        statusEl.classList.remove("hidden");
        setTimeout(() => statusEl.classList.add("hidden"), 5000);
    }
}

function showWarning(message) {
    const statusEl = document.getElementById("connection-status");
    const statusText = document.getElementById("status-text");
    if (statusEl && statusText) {
        statusEl.className =
            "mb-4 p-3 rounded-lg border bg-yellow-50 border-yellow-200";
        statusText.textContent = message;
        statusEl.classList.remove("hidden");
        setTimeout(() => statusEl.classList.add("hidden"), 5000);
    }
}

function showError(message) {
    const statusEl = document.getElementById("connection-status");
    const statusText = document.getElementById("status-text");
    if (statusEl && statusText) {
        statusEl.className =
            "mb-4 p-3 rounded-lg border bg-red-50 border-red-200";
        statusText.textContent = message;
        statusEl.classList.remove("hidden");
    }
}

function togglePassword() {
    const checkbox = document.getElementById("create_account");
    const passwordFields = document.getElementById("password-fields");

    if (checkbox && passwordFields) {
        if (checkbox.checked) {
            passwordFields.classList.remove("hidden");
            const passwordEl = document.getElementById("password");
            const passwordConfirmationEl = document.getElementById(
                "password_confirmation"
            );

            if (passwordEl) passwordEl.setAttribute("required", "required");
            if (passwordConfirmationEl)
                passwordConfirmationEl.setAttribute("required", "required");
        } else {
            passwordFields.classList.add("hidden");
            const passwordEl = document.getElementById("password");
            const passwordConfirmationEl = document.getElementById(
                "password_confirmation"
            );

            if (passwordEl) passwordEl.removeAttribute("required");
            if (passwordConfirmationEl)
                passwordConfirmationEl.removeAttribute("required");
        }
    }
}

// Order status checking functions
function checkOrderStatus(orderNumber) {
    fetch(`/api/payment/status/${orderNumber}`, {
        method: "GET",
        headers: {
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest",
        },
    })
        .then((response) => response.json())
        .then((data) => {
            console.log("💳 Order status:", data);

            if (data.status === "paid") {
                showSuccess("✅ Payment confirmed!");
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else if (data.status === "cancelled") {
                showError("❌ Order cancelled");
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            } else {
                showWarning(
                    `⏳ Order status: ${data.status_info || data.status}`
                );
            }
        })
        .catch((error) => {
            console.error("❌ Failed to check order status:", error);
            showError("Failed to check order status");
        });
}

function retryPayment(orderNumber, snapToken) {
    console.log("🔄 Retrying payment for order:", orderNumber);

    const loadingOverlay = document.getElementById("payment-loading");
    if (loadingOverlay) {
        loadingOverlay.classList.remove("hidden");
    }

    if (snapToken && snapToken !== "null" && snapToken !== "") {
        console.log("💳 Using existing snap token");

        if (typeof window.snap === "undefined") {
            loadMidtransScript()
                .then(() => {
                    openMidtransPayment(snapToken, orderNumber);
                })
                .catch(() => {
                    if (loadingOverlay) loadingOverlay.classList.add("hidden");
                    handleOrderError(
                        "Payment system not available. Please refresh the page."
                    );
                });
        } else {
            openMidtransPayment(snapToken, orderNumber);
        }
    } else {
        console.log("🔄 Generating new snap token");

        fetch(`/api/payment/retry/${orderNumber}`, {
            method: "POST",
            headers: {
                Accept: "application/json",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content"),
            },
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success && data.snap_token) {
                    console.log("✅ New snap token received");

                    if (typeof window.snap === "undefined") {
                        loadMidtransScript()
                            .then(() => {
                                openMidtransPayment(
                                    data.snap_token,
                                    orderNumber
                                );
                            })
                            .catch(() => {
                                if (loadingOverlay)
                                    loadingOverlay.classList.add("hidden");
                                handleOrderError(
                                    "Payment system not available. Please refresh the page."
                                );
                            });
                    } else {
                        openMidtransPayment(data.snap_token, orderNumber);
                    }
                } else {
                    if (loadingOverlay) loadingOverlay.classList.add("hidden");
                    handleOrderError(
                        data.error || "Failed to create payment session"
                    );
                }
            })
            .catch((error) => {
                console.error("❌ Error retrying payment:", error);
                if (loadingOverlay) loadingOverlay.classList.add("hidden");
                handleOrderError("Failed to retry payment. Please try again.");
            });
    }
}

// Auto-initialize on specific pages
if (window.location.pathname.includes("/checkout/payment/")) {
    loadMidtransScript().catch(console.error);
}

// Make functions available globally for onclick handlers
window.nextStep = nextStep;
window.prevStep = prevStep;
window.selectLocation = selectLocation;
window.clearLocation = clearLocation;
window.calculateShipping = calculateShipping;
window.selectShipping = selectShipping;
window.togglePassword = togglePassword;
window.openMidtransPayment = openMidtransPayment;
window.loadMidtransScript = loadMidtransScript;
window.retryPayment = retryPayment;
window.checkOrderStatus = checkOrderStatus;
window.loadSavedAddress = loadSavedAddress;
window.showNewAddressForm = showNewAddressForm;
window.updateAddressLabelStyles = updateAddressLabelStyles;

// Export for debugging
window.testMidtransIntegration = function () {
    console.log("🧪 Testing Midtrans integration...");

    if (typeof window.snap === "undefined") {
        console.error("❌ Midtrans Snap not available");
        return false;
    }

    console.log("✅ Midtrans Snap available");
    return true;
};

window.testAddressIntegration = function () {
    console.log("🧪 Testing address integration...");
    console.log("User has primary address:", userHasPrimaryAddress);
    console.log("Primary address ID:", primaryAddressId);
    console.log("Selected destination:", selectedDestination);

    return {
        userHasPrimaryAddress,
        primaryAddressId,
        selectedDestination,
        addressFormVisible: !document
            .getElementById("new-address-form")
            ?.classList.contains("hidden"),
    };
};

console.log(
    "🎯 Enhanced checkout with complete address integration loaded successfully!"
);
console.log("🏠 Address integration features:");
console.log("  - Auto-load primary address");
console.log("  - Saved address selection");
console.log("  - Location search with RajaOngkir API");
console.log("  - Address validation");
console.log("  - Shipping calculation integration");
console.log("💳 Payment integration features:");
console.log("  - Midtrans Snap integration");
console.log("  - COD support");
console.log("  - Payment retry functionality");
console.log("  - Order status checking");
