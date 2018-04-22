<?php
    header( 'Content-Type:text/html;charset=utf-8');
    echo str_replace('-','/',$_GET['code']);
?>