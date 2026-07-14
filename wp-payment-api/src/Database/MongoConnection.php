<?php
declare(strict_types=1);

namespace PipraPay\PaymentAPI\Database;

use MongoDB\Client;
use RuntimeException;

class MongoConnection
{
    private static ?Client $client = null;
    private static ?string $dbName = null;

    private function __construct() {}

    /**
     * Initializes the MongoDB Client using environment variables.
     * Ensures we only maintain a single connection instance.
     */
    public static function getClient(): Client
    {
        if (self::$client === null) {
            $mongoUri = getenv('MONGODB_URI') ?: 'mongodb://127.0.0.1:27017';
            self::$dbName = getenv('MONGODB_DB') ?: 'wp_payment_gateway';

            if (!$mongoUri) {
                throw new RuntimeException("MONGODB_URI is not configured in the environment.");
            }

            self::$client = new Client($mongoUri);
        }

        return self::$client;
    }

    /**
     * Returns the name of the database.
     */
    public static function getDatabaseName(): string
    {
        if (self::$dbName === null) {
            self::getClient(); // Initializes client and configures the DB name
        }
        return self::$dbName;
    }

    /**
     * Returns the selected MongoDB Database instance.
     */
    public static function getDatabase(): \MongoDB\Database
    {
        $client = self::getClient();
        return $client->selectDatabase(self::getDatabaseName());
    }
}
