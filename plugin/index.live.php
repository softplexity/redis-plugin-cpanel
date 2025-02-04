<?php
require_once "/usr/local/cpanel/php/cpanel.php";
require_once __DIR__ . "/RedisManager.php";

$cpanel = new CPANEL();
$redisManager = new RedisManager($cpanel);

try {
    $action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING) ?? 'status';
    $username = $redisManager->username;
    $userdetails = $redisManager->userdetails;

    if ($action === 'start') {
        $redisManager->startRedis();
        header("Location: index.live.php");
        exit;
    } elseif ($action === 'stop') {
        $redisManager->stopRedis();
        header("Location: index.live.php");
        exit;
    }

    // Generate Redis status details
    ob_start();
    $redisManager->checkRedisStatus();
    $status_output = ob_get_clean();

    $status_info = parseRedisStatus($status_output);

    echo generateHeader($cpanel);
    echo generateBodyContent($status_info);
    echo $cpanel->footer();
    $cpanel->end();
} catch (Exception $e) {
    echo 'Error: ' . htmlspecialchars($e->getMessage());
}

/**
 * Parses the output of the Redis status into an array of details.
 */
function parseRedisStatus($status_output)
{
    $status_info = explode(' ', $status_output);

    if (count($status_info) >= 5 && strtolower($status_info[0]) === 'running') {
        return [
            'status' => 'Running',
            'port' => $status_info[1],
            'password' => $status_info[2],
            'ip' => '127.0.0.1',
            'user' => 'root',
            'max_memory' => $status_info[3],
            'max_databases' => $status_info[4],
            'uninitiated' => 'N/A',
        ];
    } elseif (count($status_info) >= 1 && strtolower($status_info[0]) === 'uninitiated') {
        return [
            'status' => 'Not Running (Never Started)',
            'port' => 'N/A',
            'password' => 'N/A',
            'ip' => '127.0.0.1',
            'user' => 'root',
            'max_memory' => 'N/A',
            'max_databases' => 'N/A',
            'uninitiated' => $status_info[0],
        ];
    } else {
        return [
            'status' => 'Not Running (Stopped)',
            'port' => 'N/A',
            'password' => 'N/A',
            'ip' => '127.0.0.1',
            'user' => 'root',
            'max_memory' => 'N/A',
            'max_databases' => 'N/A',
            'uninitiated' => 'N/A',
        ];
    }
}

/**
 * Generates the header section with necessary styles.
 */
function generateHeader($cpanel)
{
    $stylesheets = '<link rel="stylesheet" href="redis_style.css" charset="utf-8"/>';
    return str_replace('</head>', $stylesheets . '</head>', $cpanel->header("Redis Manager"));
}

/**
 * Generates the main body content of the page.
 */
function generateBodyContent($status_info)
{
    ob_start();
    ?>
    <div class="body-content">
        <hr>
        <br>
        <p>
            <strong><a href="https://redis.io/">Redis</a></strong> is an open source, in-memory data
            store used for caching, as a vector database, streaming, and more.
        </p>
        <br>
        <div class="panel panel-default">
            <div class="panel-body">
                <div class="header-section">
                    <img src="./redis_icon.webp" alt="Redis" width="50" />
                    <h4>Redis Configuration:</h4>
                </div>
                <div class="status-section">
                    <table class="status-table">
                        <tr>
                            <th>Status:</th>
                            <td><span class="status-label <?= strtolower(str_replace(' ', '-', $status_info['status'])) ?>"><?= $status_info['status'] ?></span></td>
                        </tr>
                        <?php if ($status_info['status'] === 'Running') : ?>
                            <tr>
                                <th>IP:</th>
                                <td><?= $status_info['ip'] ?></td>
                            </tr>
                            <tr>
                                <th>Port:</th>
                                <td><?= $status_info['port'] ?></td>
                            </tr>
                            <tr>
                                <th>Password:</th>
                                <td><?= $status_info['password'] ?></td>
                            </tr>
                            <tr>
                                <th>Maximum Memory:</th>
                                <td><?= $status_info['max_memory'] ?></td>
                            </tr>
                            <tr>
                                <th>Maximum Databases:</th>
                                <td><?= $status_info['max_databases'] ?></td>
                            </tr>
                        <?php elseif ($status_info['status'] === 'Not Running (Never Started)') : ?>
                            <tr>
                                <th>Message:</th>
                                <td>Please click the button below to <strong>Start Redis</strong>. For first-time setup, this may take a few minutes.</td>
                            </tr>
                        <?php endif; ?>
                    </table>
                </div>

                <hr>
                <form method="get" class="form-inline">
                    <input type="hidden" name="action" value="<?= $status_info['status'] === 'Running' ? 'stop' : 'start' ?>">
                    <button class="btn <?= $status_info['status'] === 'Running' ? 'btn-danger' : 'btn-success' ?>" type="submit">
                        <?= $status_info['status'] === 'Running' ? 'Stop Redis' : 'Start Redis' ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
    <?php
    return ob_get_clean();
}
?>