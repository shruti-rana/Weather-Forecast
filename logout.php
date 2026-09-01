<?php
session_start();
session_destroy();
header("Location:weather.html");
exit;
?>