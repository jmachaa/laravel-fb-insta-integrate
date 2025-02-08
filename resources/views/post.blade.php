<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Post to Facebook & Instagram</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-5">
    <div class="container">
        <h1 class="mb-4">Post to Facebook & Instagram</h1>
        <form action="{{ route('social.post') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label for="message" class="form-label">Message</label>
                <textarea name="message" class="form-control" rows="3" required></textarea>
            </div>
            <div class="mb-3">
                <label for="images" class="form-label">Upload Images</label>
                <input type="file" name="images[]" class="form-control" accept="image/*" multiple >
                <input type="file" name="images[]" class="form-control" accept="image/*" multiple >
                <input type="file" name="images[]" class="form-control" accept="image/*" multiple >
                <input type="file" name="images[]" class="form-control" accept="image/*" multiple >

            </div>
            <button type="submit" class="btn btn-primary">Post Now</button>
        </form>
    </div>
</body>
</html>
