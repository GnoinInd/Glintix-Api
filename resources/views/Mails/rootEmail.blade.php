<!DOCTYPE html>
<html>
<head>
    <title>New Company Registration</title>
</head>
<body>
    <h1>New Company Registered Successfully!</h1>
    
    <p>Hello Root User,</p>
    <p>recent created company. username is {{ $companyDetails['username'] }} and password is {{ $password }}.

<p>A new company has been created with the following details:</p>

<ul>
    <li>Company Name: {{ $companyDetails['name'] }}</li>
    <li>Company Code: {{ $companyDetails['company_code'] }}</li>
    <li>DB Name: {{ $companyDetails['dbName'] }}</li>
    <li>Contact Person: {{ $companyDetails['contact_person'] }}</li>
    <li>Email: {{ $companyDetails['email'] }}</li>
    <li>Address: {{ $companyDetails['address'] }}</li>
    <li>country: {{ $companyDetails['country'] }}</li>
    <li>State: {{ $companyDetails['state'] }}</li>
    <li>Postal code: {{ $companyDetails['postal_code'] }}</li>
    <li>Fax: {{ $companyDetails['fax'] }}</li>
    <li>Phone Number: {{ $companyDetails['mobile_number'] }}</li>
    <li>website url: {{ $companyDetails['website_url'] }}</li>
</ul>

</body>
</html>