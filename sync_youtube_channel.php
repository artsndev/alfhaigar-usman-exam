<?php
// My API Key
$apiKey = 'AIzaSyCH18pSUMlNZweBiQK2VCe2_MvFBKvjxIY';

// My Youtube Channel ID
$channelId = 'UCG1VYCPWcZgqrCMI5v8UcVQ';

// Database connection
$mysqli = new mysqli("localhost", "root", "", "youtube_db");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}
function fetchFromYouTube($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_URL, $url);
    $result = curl_exec($ch);
    curl_close($ch);
    return json_decode($result, true);
}
function saveOrUpdate($mysqli, $query, $params) {
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param(...$params);
    $stmt->execute();
    $stmt->close();
}
// Get channel information
$channelInfoUrl = "https://www.googleapis.com/youtube/v3/channels?part=snippet,contentDetails,statistics&id={$channelId}&key={$apiKey}";
$channelInfo = fetchFromYouTube($channelInfoUrl)['items'][0] ?? null;
if ($channelInfo) {
    $profilePicture = $channelInfo['snippet']['thumbnails']['default']['url'];
    $name = $channelInfo['snippet']['title'];
    $description = $channelInfo['snippet']['description'];
    $uploadsPlaylistId = $channelInfo['contentDetails']['relatedPlaylists']['uploads'];
    // Save or update channel information
    saveOrUpdate(
        $mysqli,
        "INSERT INTO youtube_channels (channel_id, profile_picture, name, description) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE profile_picture = VALUES(profile_picture), name = VALUES(name), description = VALUES(description)",
        ['ssss', $channelId, $profilePicture, $name, $description]
    );
    // Fetch the latest 100 videos
    $nextPageToken = '';
    $videosFetched = 0;
    do {
        $playlistItemsUrl = "https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&maxResults=50&playlistId={$uploadsPlaylistId}&key={$apiKey}&pageToken={$nextPageToken}";
        $playlistItems = fetchFromYouTube($playlistItemsUrl);
        foreach ($playlistItems['items'] ?? [] as $item) {
            $videoId = $item['snippet']['resourceId']['videoId'];
            $title = $item['snippet']['title'];
            $description = $item['snippet']['description'];
            $thumbnail = $item['snippet']['thumbnails']['default']['url'];
            $videoLink = "https://www.youtube.com/watch?v={$videoId}";
            $publishedAt = $item['snippet']['publishedAt'];
            saveOrUpdate(
                $mysqli,
                "INSERT INTO youtube_channel_videos (channel_id, video_id, title, description, thumbnail, video_link, published_at) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE title = VALUES(title), description = VALUES(description), thumbnail = VALUES(thumbnail), video_link = VALUES(video_link), published_at = VALUES(published_at)",
                ['sssssss', $channelId, $videoId, $title, $description, $thumbnail, $videoLink, $publishedAt]
            );
            $videosFetched++;
        }
        $nextPageToken = $playlistItems['nextPageToken'] ?? '';
    } while (!empty($nextPageToken) && $videosFetched < 100);
    echo 'Successfully fetched Youtube Channel from '. $name;
}
$mysqli->close();
?>