<!DOCTYPE html>
<html>
<head>
    <title>Welcome to Gnoin Company</title>
</head>
<body>
    <p>Hello {{ $userDetails['name'] }},</p>
    <p>username is {{ $companyDetails['username'] }} and password is {{ $password }}.
    <p>A new company has been created with the following details:</p>

<ul>
    <li>Company Name: {{ $userDetails['name'] }}</li>
    <li>Company Code: {{ $userDetails['company_code'] }}</li>
    <li>Contact Person: {{ $userDetails['contact_person'] }}</li>
    <li>Email: {{ $userDetails['email'] }}</li>
    <li>Address: {{ $userDetails['address'] }}</li>
    <li>country: {{ $userDetails['country'] }}</li>
    <li>State: {{ $userDetails['state'] }}</li>
    <li>Postal code: {{ $userDetails['postal_code'] }}</li>
    <li>Fax: {{ $userDetails['fax'] }}</li>
    <li>Phone Number: {{ $userDetails['mobile_number'] }}</li>
    <li>website url: {{ $userDetails['website_url'] }}</li>
</ul>
    <p>Welcome to our Company! Thank you for registering.</p>

</body>
</html>
