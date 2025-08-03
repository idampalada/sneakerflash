// Enhanced Debug Script untuk mengatasi HTTP 500 Error
// Tambahkan script ini ke checkout page untuk debugging lebih detail

// Function untuk debug HTTP 500 Error secara detail
function debugHttpError() {
    console.log("🔍 === HTTP 500 ERROR DEBUGGING ===");

    const form = document.getElementById("checkout-form");
    if (!form) {
        console.error("❌ Form not found!");
        return;
    }

    // Test submission dengan error handling yang detail
    const formData = new FormData(form);
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");

    console.log("📤 Testing submission with detailed error catching...");

    fetch("/checkout", {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": csrfToken,
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest",
        },
        body: formData,
    })
        .then((response) => {
            console.log("📥 Response received:");
            console.log("Status:", response.status);
            console.log("Status Text:", response.statusText);
            console.log(
                "Headers:",
                Object.fromEntries(response.headers.entries())
            );

            // Coba parse response sebagai text dulu
            return response.text().then((text) => {
                console.log("📄 Raw response body:");
                console.log(text.substring(0, 1000)); // First 1000 chars

                if (response.status === 500) {
                    console.error(
                        "❌ HTTP 500 Internal Server Error detected!"
                    );
                    console.log(
                        "💡 Check Laravel logs at storage/logs/laravel.log"
                    );
                    console.log("💡 Enable APP_DEBUG=true in .env file");

                    // Coba parse sebagai JSON jika memungkinkan
                    try {
                        const json = JSON.parse(text);
                        console.log("📋 Parsed JSON response:", json);
                    } catch (e) {
                        console.log(
                            "📄 Response is not JSON, likely HTML error page"
                        );

                        // Extract error message dari HTML jika ada
                        const tempDiv = document.createElement("div");
                        tempDiv.innerHTML = text;
                        const errorElement = tempDiv.querySelector(
                            ".exception-message, .error-message, h1"
                        );
                        if (errorElement) {
                            console.log(
                                "🔍 Extracted error message:",
                                errorElement.textContent
                            );
                        }
                    }
                }

                return { status: response.status, text: text };
            });
        })
        .catch((error) => {
            console.error("❌ Network error:", error);
        });
}

// Function untuk check environment dan prerequisites
function checkEnvironment() {
    console.log("🔧 === ENVIRONMENT CHECK ===");

    // Check Laravel debug mode
    fetch("/api/debug-info", {
        method: "GET",
        headers: {
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest",
        },
    })
        .then((response) => response.json())
        .then((data) => {
            console.log("🔧 Laravel Environment:", data);
        })
        .catch((error) => {
            console.log(
                "⚠️ Could not fetch environment info (normal if endpoint doesn't exist)"
            );
        });

    // Check CSRF token validity
    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");
    if (!csrfToken) {
        console.error("❌ CSRF token not found!");
        return false;
    }

    // Test CSRF token dengan simple request
    fetch("/api/csrf-test", {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": csrfToken,
            Accept: "application/json",
            "Content-Type": "application/json",
        },
        body: JSON.stringify({ test: "csrf" }),
    })
        .then((response) => {
            if (response.status === 419) {
                console.error("❌ CSRF token invalid or expired!");
            } else if (response.status === 404) {
                console.log(
                    "✅ CSRF token appears valid (404 = endpoint not found, but token passed)"
                );
            } else {
                console.log("✅ CSRF token test:", response.status);
            }
        })
        .catch((error) => {
            console.log("⚠️ CSRF test failed:", error);
        });
}

// Function untuk check route dan controller
function checkRouteController() {
    console.log("🛣️ === ROUTE & CONTROLLER CHECK ===");

    // Test route existence dengan HEAD request
    fetch("/checkout", {
        method: "HEAD",
        headers: {
            Accept: "application/json",
        },
    })
        .then((response) => {
            console.log("🛣️ Route check (HEAD):", response.status);
            if (response.status === 405) {
                console.log(
                    "✅ Route exists but HEAD method not allowed (normal)"
                );
            } else if (response.status === 404) {
                console.error("❌ Route not found!");
            } else {
                console.log("✅ Route appears to exist");
            }
        })
        .catch((error) => {
            console.error("❌ Route check failed:", error);
        });

    // Test dengan GET request ke checkout
    fetch("/checkout", {
        method: "GET",
        headers: {
            Accept: "text/html",
        },
    })
        .then((response) => {
            console.log("🛣️ GET /checkout:", response.status);
            if (response.status === 200) {
                console.log("✅ Checkout route accessible via GET");
            } else if (response.status === 405) {
                console.log("⚠️ GET method not allowed (might be POST only)");
            }
        })
        .catch((error) => {
            console.error("❌ GET checkout failed:", error);
        });
}

// Function untuk test dengan minimal data
function testMinimalSubmission() {
    console.log("🧪 === MINIMAL SUBMISSION TEST ===");

    const csrfToken = document
        .querySelector('meta[name="csrf-token"]')
        ?.getAttribute("content");

    // Test dengan data minimal saja
    const minimalData = new FormData();
    minimalData.append("_token", csrfToken);
    minimalData.append("first_name", "Test");
    minimalData.append("last_name", "User");
    minimalData.append("email", "test@example.com");
    minimalData.append("phone", "08123456789");
    minimalData.append("address", "Test Address");
    minimalData.append("destination_id", "test_123");
    minimalData.append("destination_label", "Test Location");
    minimalData.append("postal_code", "12345");
    minimalData.append("shipping_method", "Test Shipping");
    minimalData.append("shipping_cost", "10000");
    minimalData.append("payment_method", "cod");
    minimalData.append("privacy_accepted", "1");

    console.log("📤 Submitting minimal data...");

    fetch("/checkout", {
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": csrfToken,
            Accept: "application/json",
            "X-Requested-With": "XMLHttpRequest",
        },
        body: minimalData,
    })
        .then(async (response) => {
            const text = await response.text();
            console.log("📥 Minimal submission response:");
            console.log("Status:", response.status);
            console.log("Response:", text.substring(0, 500));

            if (response.status === 500) {
                console.error("❌ Still getting 500 error with minimal data");
                console.log(
                    "💡 The issue is likely in the controller logic, not form data"
                );
            } else {
                console.log("✅ Minimal submission successful");
            }
        })
        .catch((error) => {
            console.error("❌ Minimal submission failed:", error);
        });
}

// Function untuk generate Laravel debugging commands
function generateLaravelDebugCommands() {
    console.log("🐛 === LARAVEL DEBUG COMMANDS ===");
    console.log("Run these commands in your Laravel project:");
    console.log("");
    console.log("1. Check logs:");
    console.log("   tail -f storage/logs/laravel.log");
    console.log("");
    console.log("2. Enable debug mode:");
    console.log("   APP_DEBUG=true in .env file");
    console.log("");
    console.log("3. Clear cache:");
    console.log("   php artisan cache:clear");
    console.log("   php artisan config:clear");
    console.log("   php artisan route:clear");
    console.log("   php artisan view:clear");
    console.log("");
    console.log("4. Check routes:");
    console.log("   php artisan route:list | grep checkout");
    console.log("");
    console.log("5. Test controller directly:");
    console.log("   php artisan tinker");
    console.log(
        "   >>> app('App\\Http\\Controllers\\Frontend\\CheckoutController')->index()"
    );
    console.log("");
    console.log("6. Check permissions:");
    console.log("   ls -la storage/");
    console.log("   ls -la bootstrap/cache/");
}

// Function untuk check common Laravel issues
function checkCommonLaravelIssues() {
    console.log("⚡ === COMMON LARAVEL ISSUES CHECK ===");

    // Check if we're in local development
    const hostname = window.location.hostname;
    const isLocal =
        hostname === "localhost" ||
        hostname === "127.0.0.1" ||
        hostname.includes("local");

    console.log(
        "🌍 Environment:",
        isLocal ? "Local Development" : "Production/Staging"
    );

    if (isLocal) {
        console.log("💡 Local development detected - common issues:");
        console.log("  1. Check if 'php artisan serve' is running");
        console.log("  2. Verify .env file exists and is configured");
        console.log("  3. Run 'composer install' if dependencies missing");
        console.log("  4. Check storage and bootstrap/cache permissions");
    }

    // Test basic Laravel functionality
    fetch("/", {
        method: "GET",
        headers: { Accept: "text/html" },
    })
        .then((response) => {
            console.log("🏠 Homepage response:", response.status);
            if (response.status !== 200) {
                console.error(
                    "❌ Homepage not accessible - Laravel might not be running properly"
                );
            }
        })
        .catch((error) => {
            console.error("❌ Cannot reach homepage:", error);
        });
}

// Master debug function yang menjalankan semua check
function runFullDebug() {
    console.log("🚀 === FULL DEBUG ANALYSIS ===");
    console.log("This will run all debug checks...");

    checkCommonLaravelIssues();

    setTimeout(() => {
        checkEnvironment();
    }, 1000);

    setTimeout(() => {
        checkRouteController();
    }, 2000);

    setTimeout(() => {
        debugHttpError();
    }, 3000);

    setTimeout(() => {
        testMinimalSubmission();
    }, 4000);

    setTimeout(() => {
        generateLaravelDebugCommands();
    }, 5000);

    console.log("⏳ Running all checks... Check console in next 10 seconds");
}

// Export functions
window.debugHttp500 = {
    debugHttpError,
    checkEnvironment,
    checkRouteController,
    testMinimalSubmission,
    generateLaravelDebugCommands,
    checkCommonLaravelIssues,
    runFullDebug,
};

// Auto-run basic checks
document.addEventListener("DOMContentLoaded", function () {
    console.log("🔧 === HTTP 500 DEBUG SCRIPT LOADED ===");
    console.log("Available functions:");
    console.log("  - debugHttp500.runFullDebug() - Run all checks");
    console.log("  - debugHttp500.debugHttpError() - Test actual submission");
    console.log(
        "  - debugHttp500.testMinimalSubmission() - Test with minimal data"
    );
    console.log(
        "  - debugHttp500.generateLaravelDebugCommands() - Get Laravel commands"
    );

    // Auto-run some basic checks
    setTimeout(() => {
        checkCommonLaravelIssues();
    }, 2000);
});
