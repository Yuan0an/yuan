<!DOCTYPE html>
<html>
<head>
    <title>Send Receipt</title>
</head>
<body>

<h2>Payment Form</h2>

<form action="send.php" method="POST">

    Email:
    <input type="email" name="email" required><br><br>

    Amount:
    <input type="number" name="amount" required><br><br>

    <button type="submit">Send Receipt</button>

</form>

</body>
</html>