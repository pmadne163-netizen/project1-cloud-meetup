<?php
/**
 * S3 connection helper — every meetup is one JSON object in the bucket:
 *
 *   s3://<S3_BUCKET>/<S3_PREFIX>/<meetup_id>.json
 *
 * On EC2, leave AWS_ACCESS_KEY_ID / AWS_SECRET_ACCESS_KEY blank in .env.
 * The AWS SDK's default credential provider chain will automatically pick
 * up temporary credentials from the IAM role attached to the instance
 * (see Step 1 of the deployment guide).
 */

require_once __DIR__ . '/config.php';

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

function s3(): S3Client
{
    static $client = null;

    if ($client instanceof S3Client) {
        return $client;
    }

    $config = [
        'region'  => AWS_REGION,
        'version' => 'latest',
    ];

    // Only pass explicit keys if they were actually set — otherwise let the
    // SDK fall back to the EC2 instance role.
    if (AWS_ACCESS_KEY_ID !== '' && AWS_SECRET_ACCESS_KEY !== '') {
        $config['credentials'] = [
            'key'    => AWS_ACCESS_KEY_ID,
            'secret' => AWS_SECRET_ACCESS_KEY,
        ];
    }

    $client = new S3Client($config);

    return $client;
}

/** Read and decode a JSON object from S3. Returns null if it doesn't exist. */
function s3_get_json(string $key): ?array
{
    try {
        $result = s3()->getObject([
            'Bucket' => S3_BUCKET,
            'Key' => $key,
        ]);
        $body = (string) $result['Body'];
        $data = json_decode($body, true);
        return is_array($data) ? $data : null;
    } catch (S3Exception $e) {
        if ($e->getAwsErrorCode() === 'NoSuchKey') {
            return null;
        }
        throw $e;
    }
}

/** Encode an array as JSON and write it to S3. */
function s3_put_json(string $key, array $data): void
{
    s3()->putObject([
        'Bucket' => S3_BUCKET,
        'Key' => $key,
        'Body' => json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        'ContentType' => 'application/json',
    ]);
}

/** Delete an object from S3. */
function s3_delete(string $key): void
{
    s3()->deleteObject([
        'Bucket' => S3_BUCKET,
        'Key' => $key,
    ]);
}

/** List all object keys under a prefix (e.g. S3_PREFIX). */
function s3_list_keys(string $prefix): array
{
    $keys = [];
    $paginator = s3()->getPaginator('ListObjectsV2', [
        'Bucket' => S3_BUCKET,
        'Prefix' => $prefix,
    ]);

    foreach ($paginator as $result) {
        foreach ($result['Contents'] ?? [] as $obj) {
            if (substr($obj['Key'], -5) === '.json') {
                $keys[] = $obj['Key'];
            }
        }
    }

    return $keys;
}
