// Midtrans Debug Script - Add this to your checkout page temporarily
// File: public/js/midtrans-debug.js

console.log("🔧 Midtrans Debug Script Loaded");

// Check Midtrans configuration
function debugMidtransConfig() {
    console.log("🔍 Checking Midtrans Configuration...");

    const clientKey = document
        .querySelector('meta[name="midtrans-client-key"]')
        ?.getAttribute("content");
    const isProduction = document
        .querySelector('meta[name="midtrans-production"]')
        ?.getAttribute("content");

    console.log("📋 Midtrans Config:", {
        clientKey: clientKey ? clientKey.substring(0, 15) + "..." : "NOT FOUND",
        isProduction: isProduction,
        snapUrl:
            isProduction === "true"
                ? "https://app.midtrans.com/snap/snap.js"
                : "https://app.sandbox.midtrans.com/snap/snap.js",
    });

    return { clientKey, isProduction };
}

// Test Midtrans script loading
function testMidtransScriptLoading() {
    console.log("🔄 Testing Midtrans Script Loading...");

    const config = debugMidtransConfig();

    if (!config.clientKey) {
        console.error("❌ Client key not found! Check your .env file");
        return;
    }

    // Check if script already exists
    const existingScript = document.querySelector("script[data-client-key]");
    if (existingScript) {
        console.log("📄 Midtrans script already exists:", existingScript.src);

        // Check if window.snap is available
        setTimeout(() => {
            if (window.snap) {
                console.log("✅ window.snap is available");
                console.log(
                    "🔧 Snap object:",
                    typeof window.snap,
                    Object.keys(window.snap || {})
                );
            } else {
                console.error("❌ window.snap not available after script load");
            }
        }, 2000);
    } else {
        console.log("📄 No Midtrans script found, would need to load it");
    }
}

// Test checkout form submission
function debugCheckoutSubmission() {
    console.log("🔄 Setting up checkout form debug...");

    const form = document.getElementById("checkout-form");
    if (!form) {
        console.error("❌ Checkout form not found!");
        return;
    }

    console.log("✅ Checkout form found");

    // Add debug listener
    form.addEventListener("submit", function (e) {
        console.log("🚀 Form submission intercepted for debugging");
        console.log("📋 Form data:");

        const formData = new FormData(form);
        for (let [key, value] of formData.entries()) {
            console.log(`  ${key}: ${value}`);
        }

        console.log("🔧 Current step:", window.currentStep || "unknown");
        console.log("🔧 Is submitting:", window.isSubmittingOrder || false);

        // Don't prevent the actual submission, just log it
        console.log("✅ Allowing form submission to continue...");
    });
}

// Monitor AJAX requests
function monitorAjaxRequests() {
    console.log("🔄 Setting up AJAX monitoring...");

    // Override fetch to monitor requests
    const originalFetch = window.fetch;
    window.fetch = function (...args) {
        console.log("📡 FETCH Request:", args[0], args[1]?.method || "GET");

        return originalFetch
            .apply(this, args)
            .then((response) => {
                console.log("📡 FETCH Response:", {
                    url: args[0],
                    status: response.status,
                    statusText: response.statusText,
                    contentType: response.headers.get("content-type"),
                });
                return response;
            })
            .catch((error) => {
                console.error("📡 FETCH Error:", args[0], error);
                throw error;
            });
    };
}

// Test Midtrans token simulation
function simulateMidtransToken() {
    console.log("🧪 Simulating Midtrans Token Response...");

    // Mock successful response
    const mockResponse = {
        success: true,
        message: "Order created successfully. Opening payment gateway...",
        order_number: "SF-TEST-123456",
        customer_name: "Test Customer",
        snap_token: "test-snap-token-" + Date.now(),
        total_amount: 2510000,
        discount_amount: 0,
    };

    console.log("🧪 Mock Response:", mockResponse);

    // Test if handleSuccessfulOrder function exists
    if (typeof window.handleSuccessfulOrder === "function") {
        console.log("✅ handleSuccessfulOrder function found");

        // You can uncomment this to test the function
        // window.handleSuccessfulOrder(mockResponse, 'credit_card');
    } else {
        console.error("❌ handleSuccessfulOrder function not found");
        console.log(
            "Available global functions:",
            Object.keys(window).filter(
                (key) =>
                    typeof window[key] === "function" && key.includes("handle")
            )
        );
    }
}

// Check if Midtrans is properly loaded
function checkMidtransStatus() {
    console.log("🔍 Checking Midtrans Status...");

    const status = {
        scriptLoaded: !!document.querySelector("script[data-client-key]"),
        snapAvailable: !!window.snap,
        snapMethods: window.snap ? Object.keys(window.snap) : [],
        configMeta: {
            clientKey: !!document.querySelector(
                'meta[name="midtrans-client-key"]'
            ),
            production: document
                .querySelector('meta[name="midtrans-production"]')
                ?.getAttribute("content"),
        },
    };

    console.log("📋 Midtrans Status:", status);

    if (status.scriptLoaded && !status.snapAvailable) {
        console.warn(
            "⚠️ Script loaded but snap not available - may need more time"
        );

        // Wait and check again
        setTimeout(() => {
            if (window.snap) {
                console.log("✅ Snap became available after delay");
            } else {
                console.error("❌ Snap still not available after delay");
            }
        }, 3000);
    }

    return status;
}

// Test function to manually trigger payment popup
function testPaymentPopup(snapToken = null) {
    if (!snapToken) {
        snapToken = prompt("Enter snap token to test:");
        if (!snapToken) {
            console.log("❌ No snap token provided");
            return;
        }
    }

    console.log(
        "🧪 Testing payment popup with token:",
        snapToken.substring(0, 10) + "..."
    );

    if (!window.snap) {
        console.error("❌ window.snap not available");
        return;
    }

    try {
        window.snap.pay(snapToken, {
            onSuccess: function (result) {
                console.log("✅ Test payment success:", result);
            },
            onPending: function (result) {
                console.log("⏳ Test payment pending:", result);
            },
            onError: function (result) {
                console.error("❌ Test payment error:", result);
            },
            onClose: function () {
                console.log("🔒 Test payment popup closed");
            },
        });
    } catch (error) {
        console.error("❌ Error opening test payment:", error);
    }
}

// Main debug function
function runMidtransDebug() {
    console.log("🚀 Running Midtrans Debug Suite...");
    console.log("==========================================");

    // Run all debug functions
    debugMidtransConfig();
    testMidtransScriptLoading();
    debugCheckoutSubmission();
    monitorAjaxRequests();
    checkMidtransStatus();

    console.log("==========================================");
    console.log("🔧 Debug functions available:");
    console.log("  - debugMidtransConfig()");
    console.log("  - testMidtransScriptLoading()");
    console.log("  - checkMidtransStatus()");
    console.log("  - testPaymentPopup('snap_token_here')");
    console.log("  - simulateMidtransToken()");
    console.log("==========================================");
}

// Auto-run debug on load
document.addEventListener("DOMContentLoaded", function () {
    console.log("🔧 DOM loaded, running Midtrans debug...");
    runMidtransDebug();
});

// Make functions available globally
window.debugMidtransConfig = debugMidtransConfig;
window.testMidtransScriptLoading = testMidtransScriptLoading;
window.checkMidtransStatus = checkMidtransStatus;
window.testPaymentPopup = testPaymentPopup;
window.simulateMidtransToken = simulateMidtransToken;
window.runMidtransDebug = runMidtransDebug;
