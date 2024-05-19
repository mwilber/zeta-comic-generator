<?php 
/**
 * Defines various configuration constants used throughout the application.
 * 
 * This includes credentials for external services like OpenAI and Google, as well as
 * database connection details and S3 bucket information.
 * 
 * To use, rename this file to key.php and set these constants to appropriate values 
 * before running the application.
 */

// OpenAI credentials
define("OPENAI_KEY", "");
define("GOOGLE_KEY", "");

// AWS credentials
define("AWS_ACCESS_KEY","");
define("AWS_SECRET_KEY","");
define("AWS_REGION","");

// DataBase details
define("DB_HOST", "");
define("DB_NAME", "");
define("DB_USER", "");
define("DB_PASS", "");

// S3 Bucket details
define("BUCKET_NAME","");
?>