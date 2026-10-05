-- The test environment uses the "_test" suffixed database (see config/packages/doctrine.yaml)
CREATE DATABASE IF NOT EXISTS `eventhub_test` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
GRANT ALL PRIVILEGES ON `eventhub\_test%`.* TO 'app'@'%';
FLUSH PRIVILEGES;
