<?php
require_once 'raygunSetup.php';
require_once 'viewData.php';

$viewData = new ViewData();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'partials/head.php' ?>
</head>
<body>

<div class="grid-container">
    <div class="grid-x">
        <h2>Runner's pace calculator</h2>
        <div class="cell">
            <form method="post" action="index.php">
                <div class="grid-x grid-margin-x">
                    <div class="cell medium-6">
                        <label for="time">Time (minutes)</label>
                        <input id="time" name="time" type="number" value="<?php echo $viewData->getTime(); ?>"/>
                    </div>
                    <div class="cell medium-6 grid-margin-x">
                        <label for="distance">Distance (km) <strong>DANGER: DO NOT SET TO ZERO!!!</strong></label>
                        <input id="distance" name="distance" type="number" step="0.1" value="<?php echo $viewData->getDistance(); ?>"/>
                    </div>
                </div>

                <button type="submit" class="primary button">Calculate</button>
            </form>

            <?php if ($viewData->hasSentData()) : ?>
                <div class="callout success">
                    <p><strong>Average pace:</strong> <?php echo $viewData->getAveragePace(); ?>mins/km</p>
                    <p><strong>Average speed:</strong> <?php echo $viewData->getAverageSpeed(); ?>km/hr</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>

