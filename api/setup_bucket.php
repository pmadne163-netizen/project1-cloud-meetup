<?php
/**
 * Run once from the EC2 instance to verify the S3 bucket is reachable and
 * to lay down the meetups/ prefix:
 *
 *   php api/setup_bucket.php
 *
 * Prints "Bucket is ready." when done (matches the deployment guide).
 */

require_once __DIR__ . '/../s3.php';

use Aws\S3\Exception\S3Exception;

$bucket = S3_BUCKET;

if ($bucket === '') {
    fwrite(STDERR, "S3_BUCKET is not set in .env\n");
    exit(1);
}

try {
    echo "Checking bucket '{$bucket}'...\n";
    s3()->headBucket(['Bucket' => $bucket]);
} catch (S3Exception $e) {
    fwrite(STDERR, "Could not reach bucket '{$bucket}': " . $e->getAwsErrorMessage() . "\n");
    fwrite(STDERR, "Check that the bucket exists in " . AWS_REGION . " and the EC2 IAM role has s3:ListBucket/GetObject/PutObject on it.\n");
    exit(1);
}

try {
    // Lay down a placeholder object so the "folder" is visible in the console.
    s3()->putObject([
        'Bucket' => $bucket,
        'Key' => S3_PREFIX . '.keep',
        'Body' => "KuCL Meetup Project — meetup JSON objects live under this prefix.\n",
        'ContentType' => 'text/plain',
    ]);
} catch (S3Exception $e) {
    fwrite(STDERR, "Bucket reachable, but writing failed: " . $e->getAwsErrorMessage() . "\n");
    fwrite(STDERR, "Check the IAM role's s3:PutObject permission (Step 1 of the deployment guide).\n");
    exit(1);
}

echo "Bucket is ready.\n";
