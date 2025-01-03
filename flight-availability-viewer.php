<?php
/*
Plugin Name: Flight Availability Viewer with Continent Filter
Description: Fetches and displays flight availability between continents using a specified API with authorization.
Version: 1.2
Author: Sajid Khan
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Register the admin menu.
add_action('admin_menu', function () {
    add_menu_page(
        'Flight Availability',
        'Flight Availability',
        'manage_options',
        'flight-availability',
        'render_flight_availability_page',
        'dashicons-airplane',
        20
    );
});

// Render the plugin settings page.
function render_flight_availability_page()
{
    $continents = [
        'Africa',
        'Asia',
        'Europe',
        'North America',
        'South America',
        'Oceania',
        'Antarctica'
    ];
    ?>
    <div class="wrap">
        <h1>Flight Availability Viewer</h1>
        <form method="post" action="">
            <table class="form-table">
                <tr>
                    <th><label for="source_continent">Source Continent:</label></th>
                    <td>
                        <select name="source_continent" id="source_continent" required>
                            <option value="">Select a continent</option>
                            <?php foreach ($continents as $continent): ?>
                                <option value="<?php echo esc_attr($continent); ?>">
                                    <?php echo esc_html($continent); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="destination_continent">Destination Continent:</label></th>
                    <td>
                        <select name="destination_continent" id="destination_continent" required>
                            <option value="">Select a continent</option>
                            <?php foreach ($continents as $continent): ?>
                                <option value="<?php echo esc_attr($continent); ?>">
                                    <?php echo esc_html($continent); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </table>
            <p>
                <button type="submit" name="search_flights" class="button button-primary">Search Flights</button>
            </p>
        </form>
        <?php
        if (isset($_POST['search_flights'])) {
            $source = sanitize_text_field($_POST['source_continent']);
            $destination = sanitize_text_field($_POST['destination_continent']);

            if ($source && $destination) {
                $flights = fetch_flight_availability($source, $destination);
                echo '<h2>Results</h2>';
                echo '<textarea rows="10" cols="80" readonly>';
                echo esc_html($flights);
                echo '</textarea>';
            } else {
                echo '<p style="color: red;">Please select both source and destination continents.</p>';
            }
        }
        ?>
    </div>
    <?php
}

// Fetch flight availability using the API with continent filtering.
function fetch_flight_availability($source, $destination)
{
    if (!class_exists('\GuzzleHttp\Client')) {
        require_once(plugin_dir_path(__FILE__) . 'vendor/autoload.php');
    }

    $client = new \GuzzleHttp\Client();

    try {
        // API call with source and destination as parameters (assuming API supports filtering by continents).
        $response = $client->request('GET', 'https://seats.aero/partnerapi/availability', [
            'headers' => [
                'Partner-Authorization' => 'pro_2r8UqUIxUrqzWjopSIsV0moMlpA',
                'accept' => 'application/json',
            ],
            'query' => [
                'source_continent' => $source,
                'destination_continent' => $destination,
                'take' => 500,
                'skip' => 0,
            ],
        ]);

        return $response->getBody();
    } catch (\Exception $e) {
        return 'Error fetching flight data: ' . $e->getMessage();
    }
}
