<?php
session_start();
session_destroy(); // Remove todos os dados da sessão
header("Location: login.php"); // Volta para o login
exit;
