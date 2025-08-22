// File: public/js/checkout-success.js

// Load Midtrans Script
function loadMidtransScript() {
    return new Promise((resolve, reject) => {
        if (window.snap) {
            resolve();
            return;
        }

        if (document.querySelector("script[data-client-key]")) {
            setTimeout(() => {
                if (window.snap) resolve();
                else reject(new Error("Snap object not available"));
            }, 1000);
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
            reject(new Error("Midtrans client key not found"));
            return;
        }

        const script = document.createElement("script");
        script.src = isProduction
            ? "https://app.midtrans.com/snap/snap.js"
            : "https://app.sandbox.midtrans.com/snap/snap.js";
        script.setAttribute("data-client-key", clientKey);

        script.onload = () => {
            setTimeout(() => {
                if (window.snap) resolve();
                else reject(new Error("Snap object not available"));
            }, 500);
        };

        script.onerror = () =>
            reject(new Error("Failed to load Midtrans script"));
        document.head.appendChild(script);
    });
}

// Initiate Payment Function - FIXED URL
async function initiatePayment(orderNumber) {
    console.log("🔄 Initiating payment for order:", orderNumber);

    const payButton = document.getElementById("pay-now-btn");
    const loadingOverlay = document.getElementById("payment-loading");
    const statusDiv = document.getElementById("payment-status");

    // Disable button & show loading
    payButton.disabled = true;
    payButton.innerHTML = "⏳ Processing...";
    loadingOverlay.classList.remove("hidden");
    statusDiv.classList.add("hidden");

    try {
        // Load Midtrans script first
        await loadMidtransScript();
        console.log("✅ Midtrans script loaded");

        // FIXED: Use correct URL that matches routes
        const response = await fetch(`/checkout/retry-payment/${orderNumber}`, {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": document
                    .querySelector('meta[name="csrf-token"]')
                    .getAttribute("content"),
            },
        });

        console.log("📡 Response status:", response.status);

        if (!response.ok) {
            const errorText = await response.text();
            console.error("❌ Response not OK:", response.status, errorText);
            throw new Error(`HTTP ${response.status}: ${errorText}`);
        }

        const result = await response.json();
        console.log("📨 Response data:", result);

        if (!result.success) {
            throw new Error(result.error || "Failed to create payment session");
        }

        console.log("✅ Payment retry response received:", result);

        // Check if it's a redirect response or snap token
        if (result.redirect_url) {
            console.log("🔄 Redirecting to payment page:", result.redirect_url);
            window.location.href = result.redirect_url;
            return;
        }

        if (result.snap_token) {
            console.log("✅ Snap token received:", result.snap_token);
            openMidtransPayment(result.snap_token, orderNumber);
        } else {
            throw new Error("No payment method available");
        }
    } catch (error) {
        console.error("❌ Error initiating payment:", error);
        showPaymentError("Failed to start payment: " + error.message);

        // Reset button
        payButton.disabled = false;
        payButton.innerHTML = "💳 Pay Now";
        loadingOverlay.classList.add("hidden");
    }
}

// Open Midtrans Payment Modal
function openMidtransPayment(snapToken, orderNumber) {
    console.log("💳 Opening Midtrans payment modal");

    if (!window.snap) {
        showPaymentError("Payment system not loaded. Please try again.");
        return;
    }

    updatePaymentStatus("Opening payment gateway...", "blue");

    try {
        window.snap.pay(snapToken, {
            onSuccess: function (result) {
                console.log("✅ Payment successful:", result);
                document
                    .getElementById("payment-loading")
                    .classList.add("hidden");
                updatePaymentStatus(
                    "✅ Payment successful! Redirecting...",
                    "green"
                );

                setTimeout(() => {
                    window.location.href = `/checkout/success/${orderNumber}?payment=success`;
                }, 2000);
            },

            onPending: function (result) {
                console.log("⏳ Payment pending:", result);
                document
                    .getElementById("payment-loading")
                    .classList.add("hidden");
                updatePaymentStatus(
                    "⏳ Payment is being processed. You will receive confirmation shortly.",
                    "yellow"
                );

                setTimeout(() => {
                    window.location.reload();
                }, 3000);
            },

            onError: function (result) {
                console.error("❌ Payment error:", result);
                document
                    .getElementById("payment-loading")
                    .classList.add("hidden");
                showPaymentError("Payment failed. Please try again.");
                resetPayButton();
            },

            onClose: function () {
                console.log("🔒 Payment popup closed by user");
                document
                    .getElementById("payment-loading")
                    .classList.add("hidden");

                updatePaymentStatus("Payment was cancelled.", "gray");
                resetPayButton();

                setTimeout(() => {
                    const userChoice = confirm(
                        "Payment window was closed. Would you like to try again?"
                    );

                    if (userChoice) {
                        initiatePayment(orderNumber);
                    }
                }, 1000);
            },
        });
    } catch (error) {
        console.error("❌ Error opening Midtrans:", error);
        document.getElementById("payment-loading").classList.add("hidden");
        showPaymentError("Failed to open payment gateway.");
        resetPayButton();
    }
}

// Helper Functions
function updatePaymentStatus(message, type) {
    const statusDiv = document.getElementById("payment-status");
    statusDiv.classList.remove("hidden");

    const typeClasses = {
        blue: "bg-blue-50 border-blue-200 text-blue-800",
        green: "bg-green-50 border-green-200 text-green-800",
        yellow: "bg-yellow-50 border-yellow-200 text-yellow-800",
        red: "bg-red-50 border-red-200 text-red-800",
        gray: "bg-gray-50 border-gray-200 text-gray-800",
    };

    statusDiv.innerHTML = `
        <div class="${typeClasses[type]} border rounded-lg p-4">
            <p class="font-medium">${message}</p>
        </div>
    `;
}

function showPaymentError(message) {
    updatePaymentStatus("❌ " + message, "red");
}

function resetPayButton() {
    const payButton = document.getElementById("pay-now-btn");
    if (payButton) {
        payButton.disabled = false;
        payButton.innerHTML = "💳 Pay Now";
    }
}

// Auto-load Midtrans script when page loads for pending orders
document.addEventListener("DOMContentLoaded", function () {
    const orderStatus = document
        .querySelector('meta[name="order-status"]')
        ?.getAttribute("content");
    const paymentMethod = document
        .querySelector('meta[name="payment-method"]')
        ?.getAttribute("content");

    if (orderStatus === "pending" && paymentMethod !== "cod") {
        loadMidtransScript().catch((error) => {
            console.warn("⚠️ Failed to preload Midtrans:", error);
        });
    }
});
