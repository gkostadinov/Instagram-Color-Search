<?php

// Application config
include 'config.php';

// Require API files
require_once 'libs/imagga.php';
require_once 'libs/instagram.php';

// Require essentials
require_once 'database.php';

// Application's main class
class Application
{
    private $imagga;
    private $instagram;
    private $db;

    public function __construct() {
        $this->imagga = new ColorAPIClient(IMAGGA_API_KEY, IMAGGA_API_SECRET, IMAGGA_API_ENDPOINT);
        $this->instagram = new InstagramAPIClient(INSTAGRAM_CLIENT_ID);
        $this->db = new Database(DATABASE_HOST, DATABASE_USER, DATABASE_PASSWORD, DATABASE_NAME);
    }

    public function call($method, $params=array()) {
        if (is_callable(array($this, $method))){
            return $this->$method($params);
        }
    }

    public function getInstagramPhotos($params) {
        $instagram_photos = array();
        $update = (!empty($params) && $params[0] === 'update') ? true : false;

        // Get all photos from the database
        $result = $this->db->select(array('uid', 'url'), 'photos');

        if (!$result || !$result->num_rows) {
            // No photos are added in the local database
            // We need to add new ones
            $update = true;
        } else if ($result) {
            // There are photos in the database
            // Fetch them
            while ($row = $result->fetch_assoc()){
                $instagram_photos[] = array(
                    'id' => $row['uid'],
                    'url' => $row['url']
                );
            }
            $result->free();
        }

        if ($update) {
            // Fetch new photos from Instagram
            $instagram_data = $this->instagram->get('/media/popular')->data; // Get the popular photos
            $imagga_photos = array(); // The photos added here will be processed by Imagga's API

            foreach ($instagram_data as $photo) {
                // Add them to the database
                $photo_id = $photo->created_time;
                $photo_url = $photo->images->low_resolution->url;

                $result = $this->db->insert('photos', array('uid', 'url'), array($photo_id, $photo_url));

                if ($result) {
                    $new_photo = array(
                        'id' => $photo_id,
                        'url' => $photo_url
                    );

                    // Add the new photo to the final output
                    $instagram_photos[] = $new_photo;
                    // And for color processing
                    $imagga_photos[] = $new_photo;
                }
            }

            try {
                // Parse them using Imagga's API
                $colors = $this->imagga->colors_by_urls($imagga_photos);
            }
            catch (Exception $e) {
                return $this->error($e);
            }
        }

        return $instagram_photos;
    }

    public function getMatchingPhotos($params) {
        if (empty($params)) {
            $this->error('No params set!');
        }

        $color_combination = array();
        for ($i=0; $i<count($params); $i+=4) {
            // Make the color combinations
            $color_combination[] = array(
                'percent' => $params[$i],
                'r' => $params[$i+1],
                'g' => $params[$i+2],
                'b' => $params[$i+3]
            );
        }

        try {
            // Get the matching photos using Imagga's API
            $matching_photos = $this->imagga->rank_similar_color($color_combination, 4500);

            return $matching_photos->rank_similarity;
        }
        catch (Exception $e) {
            return $this->error($e);
        }
    }

    private function error($text) {
        return array(
            'error' => true,
            'text' => 'An error occurred: '.$text
        );
    }
}

if (isset($_GET['method'])) {
    $method = $_GET['method'];
    $params = (isset($_GET['params'])) ? explode(',', $_GET['params']) : array();

    $application = new Application();
    $result = $application->call($method, $params);

    header('Content-Type: application/json');
    echo json_encode($result);
}

?>