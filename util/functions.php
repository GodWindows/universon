<?php
    require_once __DIR__ . '/../vendor/autoload.php'; 
    require_once __DIR__ . '/../env_data.php';

    function user_exists($email)
    {
        $conn = connect_database();
        if ($conn) {
            try {
                $stmt = $conn->prepare("SELECT * FROM users WHERE email  = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetchAll(PDO::FETCH_ASSOC);
                return (count($user) >= 1);
            } catch (PDOException $e) {
                if (env_type() == "dev") {
                    error_log("Error checking user existence: " . $e->getMessage());
                }
            }
        }
    }

    function create_user($email, $givenName, $picture)
    {
        $conn = connect_database();
        if ($conn) {
            try {
                $stmt = $conn->prepare("INSERT INTO users (email, firstName, picture, profile_visibility) VALUES (?, ?, ?, 'private')");
                $stmt->execute([$email, $givenName, $picture]);
            } catch (PDOException $e) {
                if (env_type() == "dev") {
                    error_log("Error creating user: " . $e->getMessage());
                }
            }
        }
    }

    function get_user_data($email)
    {
        $conn = connect_database();
        if ($conn) {
            try {
                $stmt = $conn->prepare("SELECT * FROM users WHERE email  = ?");
                $stmt->execute([$email]);
                $user = $stmt->fetchAll(PDO::FETCH_ASSOC);
                return $user[0];
            } catch (PDOException $e) {
                if (env_type() == "dev") {
                    error_log("Error fetching user data: " . $e->getMessage());
                }
            }
        }
    }

    function get_profile_visibility($email)
    {
        $conn = connect_database();
        if ($conn) {
            try {
                $stmt = $conn->prepare("SELECT profile_visibility FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                return $result ? $result['profile_visibility'] : 'private';
            } catch (PDOException $e) {
                if (env_type() == "dev") {
                    error_log("Error fetching profile visibility: " . $e->getMessage());
                }
                return 'private';
            }
        }
        return 'private';
    }

    function update_user_bio($email, $bio)
    {
        $conn = connect_database();
        if ($conn) {
            try {
                $stmt = $conn->prepare("UPDATE users SET bio = ? WHERE email = ?");
                $result = $stmt->execute([$bio, $email]);
                return $result;
            } catch (PDOException $e) {
                if (env_type() == "dev") {
                    error_log("Error updating bio: " . $e->getMessage());
                }
                return false;
            }
        }
        return false;
    }

    function update_profile_visibility($email, $visibility)
    {
        $conn = connect_database();
        if ($conn) {
            try {
                if (!in_array($visibility, ['private', 'public'])) {
                    return false;
                }
                $stmt = $conn->prepare("UPDATE users SET profile_visibility = ? WHERE email = ?");
                $result = $stmt->execute([$visibility, $email]);
                return $result;
            } catch (PDOException $e) {
                if (env_type() == "dev") {
                    error_log("Error updating profile visibility: " . $e->getMessage());
                }
                return false;
            }
        }
        return false;
    }

    function check_pseudo_availability($pseudo)
    {
        $conn = connect_database();
        if ($conn) {
            try {
                $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE pseudo = ?");
                $stmt->execute([$pseudo]);
                $count = $stmt->fetchColumn();
                return $count === 0;
            } catch (PDOException $e) {
                if (env_type() == "dev") {
                    error_log("Error checking pseudo availability: " . $e->getMessage());
                }
                return false;
            }
        }
        return false;
    }

    function update_user_pseudo($email, $pseudo)
    {
        $conn = connect_database();
        if ($conn) {
            try {
                if (!check_pseudo_availability($pseudo)) {
                    return false;
                }
                $stmt = $conn->prepare("UPDATE users SET pseudo = ? WHERE email = ?");
                $result = $stmt->execute([$pseudo, $email]);
                return $result;
            } catch (PDOException $e) {
                if (env_type() == "dev") {
                    error_log("Error updating pseudo: " . $e->getMessage());
                }
                return false;
            }
        }
        return false;
    }

    function get_user_albums($userId)
    {
        // Now get albums from the 'favorite' category instead of user_albums table
        return get_user_albums_by_category($userId, 'favorite');
    }

    function add_album_to_user($userId, $albumName)
    {
        // Create album data and add to favorite category
        $albumData = [
            'album_name' => $albumName,
            'external_album_id' => null,
            'external_artist_id' => null,
            'artist_name' => null,
            'image_url_60' => null,
            'image_url_100' => null
        ];
        return add_album_to_category($userId, $albumData, 'favorite');
    }

    function remove_album_from_user($userId, $albumId)
    {
        // Remove from favorite category instead of user_albums table
        return remove_album_from_category($userId, $albumId, 'favorite');
    }

    function saveSessionToDb($sessionToken, $googleAccessToken, $email) {
        $conn = connect_database();
        if ($conn) {
            try {
                $stmt = $conn->prepare("INSERT INTO sessions (session_token, google_access_token, email) VALUES (:token, :access, :email)");
                $stmt->execute([
                    ':token' => $sessionToken,
                    ':email' => $email,
                    ':access' => $googleAccessToken,
                ]);
                if (env_type() == "dev") {
                    echo "Session saved successfully!<br>";
                }
            } catch (PDOException $e) {
                if (env_type() == "dev") {
                    echo "Error saving session: " . $e->getMessage() . "<br>";
                }
                error_log("Error saving session: " . $e->getMessage());
            }
        } else {
            if (env_type() == "dev") {
                echo "Database connection failed in saveSessionToDb!<br>";
            }
            error_log("Database connection failed in saveSessionToDb");
        }
        
    }

    function deleteSessionFromDb($sessionToken) {
        $conn = connect_database();
        if ($conn) {
            try {
                $stmt = $conn->prepare("DELETE FROM sessions WHERE session_token = :token");
                $stmt->execute([':token' => $sessionToken]);
            } catch (PDOException $e) {
                if (env_type() == "dev") {
                    echo "Error: " . $e->getMessage();
                }
            }
        }
    }

    function logout($sessionToken, $client) {
        setcookie("session_token", "", time() - 3600, "/", "", true, true);

        $conn = connect_database();
        if ($conn) {
            try {
                $stmt = $conn->prepare("SELECT google_access_token FROM sessions WHERE session_token = :token LIMIT 1");
                $stmt->execute([':token' => $sessionToken]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row && !empty($row['google_access_token'])) {
                    $accessToken = $row['google_access_token'];
                    $client->setAccessToken($accessToken);
                    try {
                        $client->revokeToken();
                    } catch (Exception $e) {
                        if (env_type() == "dev") {
                            error_log("Erreur lors de la révocation Google: " . $e->getMessage());
                        }
                    }
                }
            } catch (PDOException $e) {
                if (env_type() == "dev") {
                    echo "Error: " . $e->getMessage();
                }
            }
        }
        deleteSessionFromDb($sessionToken);
    }

    function getUserFromSessionToken($sessionToken) {
        $conn = connect_database();
        if ($conn) {
            try {
                $stmt = $conn->prepare("SELECT email FROM sessions WHERE session_token = :token LIMIT 1");
                $stmt->execute([':token' => $sessionToken]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
                if (!$row) {
                    return null;
                }
            
                $email = $row['email'];
            
                return get_user_data($email);
            } catch (PDOException $e) {
                if (env_type() == "dev") {
                    echo "Error: " . $e->getMessage();
                }
            }
        }
    }

    function get_user_public_min_by_pseudo($pseudo)
    {
        $conn = connect_database();
        if ($conn) {
            try {
                $stmt = $conn->prepare("SELECT id, pseudo, firstName, bio, picture, profile_visibility FROM users WHERE pseudo = ? LIMIT 1");
                $stmt->execute([$pseudo]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                return $row ?: null;
            } catch (PDOException $e) {
                if (env_type() == "dev") {
                    error_log("Error fetching user by pseudo: " . $e->getMessage());
                }
                return null;
            }
        }
        return null;
    }

    function add_or_get_album_with_metadata_and_link_user($userId, $albumData)
    {
        $conn = connect_database();
        if (!$conn) {
            return false;
        }
        try {
            $conn->beginTransaction();

            $externalAlbumId = isset($albumData["external_album_id"]) ? $albumData["external_album_id"] : null;
            $albumName = isset($albumData["album_name"]) ? $albumData["album_name"] : null;
            $externalArtistId = isset($albumData["external_artist_id"]) ? $albumData["external_artist_id"] : null;
            $artistName = isset($albumData["artist_name"]) ? $albumData["artist_name"] : null;
            $image60 = isset($albumData["image_url_60"]) ? $albumData["image_url_60"] : null;
            $image100 = isset($albumData["image_url_100"]) ? $albumData["image_url_100"] : null;

            if (!empty($externalAlbumId)) {
                $stmt = $conn->prepare("SELECT id FROM albums WHERE external_album_id = ? LIMIT 1");
                $stmt->execute([$externalAlbumId]);
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($existing && isset($existing["id"])) {
                    $albumId = $existing["id"];
                } else {
                    $stmt = $conn->prepare("INSERT INTO albums (external_album_id, external_artist_id, name, artist_name, image_url_60, image_url_100) VALUES (?, ?, ?, ?, ?, ?) ");
                    $stmt->execute([$externalAlbumId, $externalArtistId, $albumName, $artistName, $image60, $image100]);
                    $albumId = $conn->lastInsertId();
                }
            } else {
                $stmt = $conn->prepare("INSERT INTO albums (name, artist_name, image_url_60, image_url_100) VALUES (?, ?, ?, ?)");
                $stmt->execute([$albumName, $artistName, $image60, $image100]);
                $albumId = $conn->lastInsertId();
            }

            // No longer need to link to user_albums table since we use categories directly

            $conn->commit();
            return $albumId;
        } catch (PDOException $e) {
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            error_log("Error adding album with metadata: " . $e->getMessage());
            return false;
        }
    }

    // Fonctions pour gérer les catégories d'albums
    function get_user_albums_by_category($userId, $categoryName)
    {
        $conn = connect_database();
        if ($conn) {
            try {
                $stmt = $conn->prepare("
                    SELECT a.id, a.name, a.artist_name, a.image_url_60, a.image_url_100, a.created_at, uac.created_at as categorized_at
                    FROM albums a
                    INNER JOIN user_album_categories uac ON a.id = uac.album_id
                    INNER JOIN album_categories ac ON uac.category_id = ac.id
                    WHERE uac.user_id = ? AND ac.name = ?
                    ORDER BY uac.created_at DESC
                ");
                $stmt->execute([$userId, $categoryName]);
                return $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                if (env_type() == "dev") {
                    error_log("Error fetching user albums by category: " . $e->getMessage());
                }
                return [];
            }
        }
        return [];
    }

    function validate_category_exists($categoryName)
    {
        $conn = connect_database();
        if (!$conn) {
            return false;
        }
        
        try {
            $stmt = $conn->prepare("SELECT id FROM album_categories WHERE name = ?");
            $stmt->execute([$categoryName]);
            $category = $stmt->fetch(PDO::FETCH_ASSOC);
            return $category !== false;
        } catch (PDOException $e) {
            if (env_type() == "dev") {
                error_log("Error validating category: " . $e->getMessage());
            }
            return false;
        }
    }

    function add_album_to_category($userId, $albumData, $categoryName)
    {
        $conn = connect_database();
        if ($conn) {
            try {
                $conn->beginTransaction();
                
                // Récupérer l'ID de la catégorie
                $stmt = $conn->prepare("SELECT id FROM album_categories WHERE name = ?");
                $stmt->execute([$categoryName]);
                $category = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (!$category) {
                    $conn->rollBack();
                    return false;
                }
                
                $externalAlbumId = isset($albumData["external_album_id"]) ? $albumData["external_album_id"] : null;
                $albumName = isset($albumData["album_name"]) ? $albumData["album_name"] : null;
                $externalArtistId = isset($albumData["external_artist_id"]) ? $albumData["external_artist_id"] : null;
                $artistName = isset($albumData["artist_name"]) ? $albumData["artist_name"] : null;
                $image60 = isset($albumData["image_url_60"]) ? $albumData["image_url_60"] : null;
                $image100 = isset($albumData["image_url_100"]) ? $albumData["image_url_100"] : null;
                
                $albumId = null;
                
                // Check if album exists in albums table by external_album_id
                if (!empty($externalAlbumId)) {
                    $stmt = $conn->prepare("SELECT id FROM albums WHERE external_album_id = ? LIMIT 1");
                    $stmt->execute([$externalAlbumId]);
                    $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($existing && isset($existing["id"])) {
                        $albumId = $existing["id"];
                    }
                }
                
                // If album doesn't exist, create it
                if (!$albumId) {
                    if (!empty($externalAlbumId)) {
                        $stmt = $conn->prepare("INSERT INTO albums (external_album_id, external_artist_id, name, artist_name, image_url_60, image_url_100) VALUES (?, ?, ?, ?, ?, ?)");
                        $stmt->execute([$externalAlbumId, $externalArtistId, $albumName, $artistName, $image60, $image100]);
                        $albumId = $conn->lastInsertId();
                    } else {
                        $stmt = $conn->prepare("INSERT INTO albums (name, artist_name, image_url_60, image_url_100) VALUES (?, ?, ?, ?)");
                        $stmt->execute([$albumName, $artistName, $image60, $image100]);
                        $albumId = $conn->lastInsertId();
                    }
                }
                
                // Add album to category
                $stmt = $conn->prepare("INSERT INTO user_album_categories (user_id, album_id, category_id) VALUES (?, ?, ?)");
                $stmt->execute([$userId, $albumId, $category['id']]);
                
                $conn->commit();
                return true;
            } catch (PDOException $e) {
                if ($conn->inTransaction()) {
                    $conn->rollBack();
                }
                if (env_type() == "dev") {
                    error_log("Error adding album to category: " . $e->getMessage());
                }
                return false;
            }
        }
        return false;
    }

    function remove_album_from_category($userId, $albumId, $categoryName)
    {
        $conn = connect_database();
        if ($conn) {
            try {
                $stmt = $conn->prepare("
                    DELETE uac FROM user_album_categories uac
                    INNER JOIN album_categories ac ON uac.category_id = ac.id
                    WHERE uac.user_id = ? AND uac.album_id = ? AND ac.name = ?
                ");
                $result = $stmt->execute([$userId, $albumId, $categoryName]);
                return $result;
            } catch (PDOException $e) {
                if (env_type() == "dev") {
                    error_log("Error removing album from category: " . $e->getMessage());
                }
                return false;
            }
        }
        return false;
    }

    function is_album_in_category($userId, $albumId, $categoryName)
    {
        $conn = connect_database();
        if ($conn) {
            try {
                $stmt = $conn->prepare("
                    SELECT COUNT(*) FROM user_album_categories uac
                    INNER JOIN album_categories ac ON uac.category_id = ac.id
                    WHERE uac.user_id = ? AND uac.album_id = ? AND ac.name = ?
                ");
                $stmt->execute([$userId, $albumId, $categoryName]);
                $count = $stmt->fetchColumn();
                return $count > 0;
            } catch (PDOException $e) {
                if (env_type() == "dev") {
                    error_log("Error checking album category: " . $e->getMessage());
                }
                return false;
            }
        }
        return false;
    }



// Get Spotify access token
function getSpotifyToken($spotify_client_id, $spotify_client_secret) {
    $url = 'https://accounts.spotify.com/api/token';
    
    // Create basic auth header
    $auth = base64_encode($spotify_client_id . ':' . $spotify_client_secret);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Basic ' . $auth,
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200) {
        $data = json_decode($response, true);
        return $data['access_token'] ?? null;
    }
    
    return null;
}
?>
