<?php
require_once 'raygunSetup.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Synchronous example</title>
</head>
<body>

<h1>Synchronous example app</h1>

<form method="post" action="index.php">
    <fieldset>
        <label for="num">Number to divide 42 by</label>
        <input type="number" id="num" name="num" value="<?php echo $_POST['num'] ?? 0; ?>"/>
    </fieldset>

    <button type="submit">Submit</button>

    <div>Result: <?php echo 42 / $_POST['num']; ?></div>
</form>
</body>
</html>

