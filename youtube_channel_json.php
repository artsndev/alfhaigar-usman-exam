<?php
header("Access-Control-Allow-Origin: *");
header('Content-Type: application/json');
$mysqli = new mysqli("localhost", "root", "", "youtube_db");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Get channel information
$channelQuery = $mysqli->query("SELECT * FROM youtube_channels WHERE channel_id = 'UCG1VYCPWcZgqrCMI5v8UcVQ'");
$channel = $channelQuery->fetch_assoc();

// Get videos information
$videosQuery = $mysqli->query("SELECT * FROM youtube_channel_videos WHERE channel_id = 'UCG1VYCPWcZgqrCMI5v8UcVQ' ORDER BY published_at DESC LIMIT 100");
$videos = [];
while ($video = $videosQuery->fetch_assoc()) {
    $videos[] = $video;
}

$response = [
    'channel' => $channel,
    'videos' => $videos
];

echo json_encode($response);

$mysqli->close();
?>