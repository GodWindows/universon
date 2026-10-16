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

    /* Pochettes des profils publics, pour le mur du bandeau d'accueil (§ 6.1).
       Tirées au hasard ; si les profils publics en comptent moins que $count,
       la liste est répétée pour remplir le mur. */
    function get_public_wall_covers($count)
    {
        $conn = connect_database();
        if (!$conn) {
            return [];
        }
        try {
            $stmt = $conn->prepare("
                SELECT DISTINCT a.id, a.name, a.artist_name, a.image_url_100
                FROM albums a
                INNER JOIN user_album_categories uac ON a.id = uac.album_id
                INNER JOIN users u ON uac.user_id = u.id
                WHERE u.profile_visibility = 'public' AND a.image_url_100 IS NOT NULL AND a.image_url_100 <> ''
                ORDER BY RAND()
                LIMIT " . (int) $count);
            $stmt->execute();
            $covers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            if (env_type() == "dev") {
                error_log("Error fetching wall covers: " . $e->getMessage());
            }
            return [];
        }
        if (!$covers) {
            return [];
        }
        // Les vignettes iTunes existent en plusieurs tailles : 300px reste net en 2x.
        foreach ($covers as &$cover) {
            $cover['image_url'] = str_replace('/100x100bb.', '/300x300bb.', $cover['image_url_100']);
        }
        unset($cover);
        $wall = [];
        for ($i = 0; count($wall) < $count; $i++) {
            $wall[] = $covers[$i % count($covers)];
        }
        return $wall;
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

    /* Avis de l'utilisateur, indépendants des listes (reviews.md R10),
       du plus récemment modifié au plus ancien. */
    function get_user_reviews($userId)
    {
        $conn = connect_database();
        if (!$conn) {
            return [];
        }
        try {
            $stmt = $conn->prepare("
                SELECT a.id, a.external_album_id, a.name, a.artist_name, a.image_url_60, a.image_url_100,
                       ar.rating, ar.body AS review
                FROM album_reviews ar
                INNER JOIN albums a ON a.id = ar.album_id
                WHERE ar.user_id = ?
                ORDER BY ar.updated_at DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            if (env_type() == "dev") {
                error_log("Error fetching user reviews: " . $e->getMessage());
            }
            return [];
        }
    }

    /* Note (1–10 demi-étoiles, ou null) et avis d'un album. Sans note ni
       avis, la ligne est supprimée. Retourne l'id de l'album, ou false. */
    function save_album_review($userId, $albumId, $albumData, $rating, $body)
    {
        $conn = connect_database();
        if (!$conn) {
            return false;
        }
        try {
            if (!$albumId) {
                $albumId = find_or_create_album($conn, $albumData);
                if (!$albumId) {
                    return false;
                }
            }
            if ($rating === null && $body === '') {
                $stmt = $conn->prepare("DELETE FROM album_reviews WHERE user_id = ? AND album_id = ?");
                $stmt->execute([$userId, $albumId]);
                return $albumId;
            }
            $stmt = $conn->prepare("
                INSERT INTO album_reviews (user_id, album_id, rating, body) VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE rating = VALUES(rating), body = VALUES(body)
            ");
            $stmt->execute([$userId, $albumId, $rating, $body === '' ? null : $body]);
            return $albumId;
        } catch (PDOException $e) {
            if (env_type() == "dev") {
                error_log("Error saving album review: " . $e->getMessage());
            }
            return false;
        }
    }

    /* Note en étoiles (reviews.md R2) : seules les étoiles obtenues, demie comprise. */
    function rating_stars($rating)
    {
        $rating = (int) $rating;
        if ($rating < 1 || $rating > 10) {
            return '';
        }
        $html = '<span class="stars" role="img" aria-label="'
              . htmlspecialchars(t('review.rating.aria', ['value' => rating_value($rating)])) . '">';
        $html .= str_repeat('<i class="star"></i>', intdiv($rating, 2));
        if ($rating % 2) {
            $html .= '<i class="star star--half"></i>';
        }
        return $html . '</span>';
    }

    /* 7 → « 3,5 » (décimale selon la langue). */
    function rating_value($rating)
    {
        return str_replace('.', t('review.decimal'), (string) ($rating / 2));
    }

    /* Onglets « Listes » / « Avis » (reviews.md R3). Sans script, ce sont des
       ancres vers les deux panneaux, tous deux visibles. */
    function profile_tabs($listCount, $reviewCount)
    {
        $tabs = [
            'lists'   => ['section.lists', $listCount, true],
            'reviews' => ['section.reviews', $reviewCount, false],
        ];
        $html = '<nav class="profile-tabs" role="tablist" aria-label="' . htmlspecialchars(t('tabs.label')) . '">';
        foreach ($tabs as $id => [$key, $count, $selected]) {
            $html .= '<a href="#' . $id . '" class="profile-tab" role="tab" id="tab-' . $id . '"'
                   . ' aria-controls="' . $id . '" aria-selected="' . ($selected ? 'true' : 'false') . '"'
                   . ($selected ? '' : ' tabindex="-1"') . '>'
                   . e($key) . '<span class="tab-count">' . (int) $count . '</span></a>';
        }
        return $html . '</nav>';
    }

    /* Liste d'avis (reviews.md R4). $editable ajoute le bouton « Modifier ». */
    function review_list(array $reviews, $editable)
    {
        $html = '<ul class="review-list">';
        foreach ($reviews as $review) {
            $cover = !empty($review['image_url_100']) ? $review['image_url_100'] : ($review['image_url_60'] ?? '');
            $html .= '<li class="review-item">';
            $html .= '<div class="review-item-cover">'
                   . ($cover ? '<img src="' . htmlspecialchars($cover) . '" alt="" loading="lazy" onerror="this.remove();">' : '')
                   . '</div>';
            $html .= '<div class="review-item-body">'
                   . '<h3 class="review-item-title">' . htmlspecialchars($review['name']) . '</h3>';
            if (!empty($review['artist_name'])) {
                $html .= '<p class="mo review-item-artist">' . htmlspecialchars($review['artist_name']) . '</p>';
            }
            if (!empty($review['rating'])) {
                $html .= '<p class="review-score">' . rating_stars($review['rating'])
                       . '<span class="rating-value">' . e('review.rating.out_of', ['value' => rating_value($review['rating'])]) . '</span></p>';
            }
            if (!empty($review['review'])) {
                $html .= '<p class="review-text">' . htmlspecialchars($review['review']) . '</p>';
            }
            $html .= '</div>';
            if ($editable) {
                $html .= '<button type="button" class="btn btn-line btn--sm review-edit"'
                       . ' aria-label="' . htmlspecialchars(t('review.edit.aria', ['album' => $review['name']])) . '"'
                       . ' data-album-id="' . (int) $review['id'] . '"'
                       . ' data-external-id="' . htmlspecialchars((string) ($review['external_album_id'] ?? '')) . '"'
                       . ' data-rating="' . htmlspecialchars((string) ($review['rating'] ?? '')) . '"'
                       . ' data-review="' . htmlspecialchars((string) ($review['review'] ?? '')) . '"'
                       . ' data-title="' . htmlspecialchars($review['name']) . '"'
                       . ' data-artist="' . htmlspecialchars((string) ($review['artist_name'] ?? '')) . '"'
                       . ' data-cover="' . htmlspecialchars((string) $cover) . '">'
                       . e('review.edit') . '</button>';
            }
            $html .= '</li>';
        }
        return $html . '</ul>';
    }

    /* Fiche d'écriture du tableau de bord (reviews.md R5), en deux étapes. */
    function review_sheet()
    {
        $html = '<dialog id="reviewSheet" class="review-sheet" data-step="pick" aria-labelledby="reviewTitle">'
              . '<div class="review-head">'
              . '<img class="review-cover" src="" alt="" width="96" height="96">'
              . '<div>'
              . '<span class="mo review-eyebrow">' . e('review.eyebrow') . '</span>'
              . '<h2 id="reviewTitle"></h2>'
              . '<p class="mo review-artist"></p>'
              . '</div></div>'
              . '<div class="review-main">'
              . '<div class="album-input-group review-pick">'
              . '<label for="reviewAlbumInput" class="field-label">' . e('review.pick.label') . '</label>'
              . '<input type="text" id="reviewAlbumInput" class="album-input" placeholder="' . htmlspecialchars(t('review.pick.placeholder')) . '" maxlength="255" autocomplete="off">'
              . '<div class="album-suggestions" id="reviewAlbumSuggestions" style="display:none;"></div>'
              . '</div>'
              . '<div class="review-write">'
              . '<fieldset class="rating-input">'
              . '<legend class="field-label">' . e('review.rating.label') . '</legend>'
              . '<div class="rating-row">'
              . '<div class="rating-stars" aria-hidden="true">' . str_repeat('<i class="star star--off"></i>', 5) . '</div>'
              . '<span class="rating-value"></span>'
              . '<button type="button" class="btn btn-line btn--sm rating-clear" hidden>' . e('review.rating.clear') . '</button>'
              . '</div>';
        for ($v = 1; $v <= 10; $v++) {
            $html .= '<input type="radio" name="rating" value="' . $v . '" class="sr-only"'
                   . ' aria-label="' . htmlspecialchars(t('review.rating.aria', ['value' => rating_value($v)])) . '">';
        }
        return $html . '</fieldset>'
             . '<label for="reviewBody" class="field-label review-body-label">' . e('review.body.label') . '</label>'
             . '<textarea id="reviewBody" class="field review-body" maxlength="2000" rows="6"></textarea>'
             . '<p class="mo review-count"></p>'
             . '</div></div>'
             . '<div class="modal-foot">'
             . '<button type="button" class="btn btn-line review-delete" hidden>' . e('review.delete') . '</button>'
             . '<button type="button" class="btn btn-line review-cancel">' . e('review.cancel') . '</button>'
             . '<button type="button" class="btn review-save" disabled>' . e('review.save') . '</button>'
             . '</div></dialog>';
    }

    /* Album par identifiant Deezer, créé s'il n'existe pas. Titre, artiste et
       pochette viennent de l'API Deezer, jamais du navigateur. Sans identifiant
       (saisie libre), l'album garde le nom tapé, sans pochette.
       Retourne son id, ou null si l'album est introuvable. */
    function find_or_create_album($conn, $albumData)
    {
        $externalAlbumId = isset($albumData["external_album_id"]) && is_scalar($albumData["external_album_id"])
            ? trim((string) $albumData["external_album_id"]) : '';

        // Saisie libre : nom et artiste tapés, aucune image venant du navigateur
        if ($externalAlbumId === '') {
            $albumName = isset($albumData["album_name"]) && is_string($albumData["album_name"]) ? trim($albumData["album_name"]) : '';
            $artistName = isset($albumData["artist_name"]) && is_string($albumData["artist_name"]) ? trim($albumData["artist_name"]) : '';
            if ($albumName === '') {
                return null;
            }
            $stmt = $conn->prepare("INSERT INTO albums (name, artist_name) VALUES (?, ?)");
            $stmt->execute([$albumName, $artistName === '' ? null : $artistName]);
            return $conn->lastInsertId();
        }

        // Album déjà connu : réutilisé tel quel
        $stmt = $conn->prepare("SELECT id FROM albums WHERE external_album_id = ? LIMIT 1");
        $stmt->execute([$externalAlbumId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existing) {
            return $existing["id"];
        }

        // Nouvel album : ses données viennent de Deezer
        $album = fetch_deezer_album($externalAlbumId);
        if (!$album) {
            return null;
        }
        $stmt = $conn->prepare("INSERT INTO albums (external_album_id, external_artist_id, name, artist_name, image_url_60, image_url_100) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$externalAlbumId, $album['artist_id'], $album['name'], $album['artist_name'], $album['image_url_60'], $album['image_url_100']]);
        return $conn->lastInsertId();
    }

    /* Album Deezer (api.deezer.com/album/{id}) : titre, artiste, pochettes. Ou null. */
    function fetch_deezer_album($deezerId)
    {
        if (!ctype_digit($deezerId)) {
            return null;
        }
        $ch = curl_init('https://api.deezer.com/album/' . $deezerId);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        // Un identifiant inconnu renvoie aussi 200, avec une clé "error"
        $data = $http_code === 200 ? json_decode($response, true) : null;
        if (!is_array($data) || isset($data['error']) || empty($data['title'])) {
            return null;
        }
        return [
            'name'          => $data['title'],
            'artist_id'     => $data['artist']['id'] ?? null,
            'artist_name'   => $data['artist']['name'] ?? null,
            'image_url_60'  => $data['cover_small'] ?? null,
            'image_url_100' => $data['cover_medium'] ?? null,
        ];
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
                
                $albumId = find_or_create_album($conn, $albumData);
                if (!$albumId) {
                    $conn->rollBack();
                    return false;
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
