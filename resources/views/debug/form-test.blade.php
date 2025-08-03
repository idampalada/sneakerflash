<!-- Simple form test for debugging -->
<!DOCTYPE html>
<html>
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Form Test</title>
</head>
<body>
    <h1>Debug Form Test</h1>
    
    <form id="test-form" action="/debug/test-checkout" method="POST">
        @csrf
        <input type="text" name="test_field" value="test_value">
        <input type="checkbox" name="test_checkbox" value="1" checked>
        <button type="submit">Submit Test</button>
    </form>

    <script>
    document.getElementById('test-form').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        
        fetch('/debug/test-checkout', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            console.log('Success:', data);
            alert('Test successful: ' + JSON.stringify(data, null, 2));
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Test failed: ' + error.message);
        });
    });
    </script>
</body>
</html>