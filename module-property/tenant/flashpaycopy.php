<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenant Application Form</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 50%;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            color: #333;
        }
        label {
            display: block;
            margin: 15px 0 5px;
            color: #555;
        }
        input[type="text"],
        input[type="email"],
        input[type="tel"],
        textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        textarea {
            resize: vertical;
        }
        .submit-btn {
            display: block;
            width: 100%;
            padding: 10px;
            background-color: #28a745;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .submit-btn:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Tenant Application Form</h2>
        <form action="/submit_application" method="post">
            <label for="fullName">Full Name</label>
            <input type="text" id="fullName" name="fullName" required>

            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required>

            <label for="phone">Phone Number</label>
            <input type="tel" id="phone" name="phone" required>

            <label for="currentAddress">Current Address</label>
            <textarea id="currentAddress" name="currentAddress" rows="4" required></textarea>

            <label for="employmentStatus">Employment Status</label>
            <input type="text" id="employmentStatus" name="employmentStatus" required>

            <label for="monthlyIncome">Monthly Income</label>
            <input type="text" id="monthlyIncome" name="monthlyIncome" required>

            <label for="references">References</label>
            <textarea id="references" name="references" rows="4" required></textarea>

            <button type="submit" class="submit-btn">Submit Application</button>
        </form>
    </div>
</body>
</html>
