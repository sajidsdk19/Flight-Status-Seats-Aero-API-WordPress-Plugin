<?php
/*
Plugin Name: Flight Availability Viewer with Continent Filter
Description: Fetches and displays flight availability between continents using a specified API with authorization.
Version: 2.0
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

// Register the shortcode for Elementor and front-end use.
add_shortcode('flight_availability', 'render_flight_availability_page');

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

    // Get previously submitted values.
    $selected_source = isset($_POST['source_continent']) ? sanitize_text_field($_POST['source_continent']) : '';
    $selected_destination = isset($_POST['destination_continent']) ? sanitize_text_field($_POST['destination_continent']) : '';

    ob_start();
    ?>
    <div class="wrap">
        <h1>Flight Availability Viewer</h1>
        <form method="post" action="">
            <div style="display: flex; gap: 10px; align-items: center;">
                <select name="source_continent" id="source_continent" required>
                    <option value="">Select Source Continent</option>
                    <?php foreach ($continents as $continent): ?>
                        <option value="<?php echo esc_attr($continent); ?>" <?php selected($selected_source, $continent); ?>>
                            <?php echo esc_html($continent); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select name="destination_continent" id="destination_continent" required>
                    <option value="">Select Destination Continent</option>
                    <?php foreach ($continents as $continent): ?>
                        <option value="<?php echo esc_attr($continent); ?>" <?php selected($selected_destination, $continent); ?>>
                            <?php echo esc_html($continent); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" name="search_flights" class="button button-primary">Search Flights</button>
            </div>
        </form>
        <?php
        if (isset($_POST['search_flights'])) {
            if ($selected_source && $selected_destination) {
                $flights = fetch_flight_availability($selected_source, $selected_destination);
                if ($flights) {
                    echo '<h2>Flight Results</h2>';
                    echo render_dynamic_flight_table($flights);
                } else {
                    echo '<p style="color: red;">No flights found or an error occurred.</p>';
                }
            } else {
                echo '<p style="color: red;">Please select both source and destination continents.</p>';
            }
        }
        ?>
    </div>
    <?php
    return ob_get_clean();
}

// Fetch flight availability using the API with continent filtering.
function fetch_flight_availability($source, $destination)
{
    if (!class_exists('\GuzzleHttp\Client')) {
        require_once(plugin_dir_path(__FILE__) . 'vendor/autoload.php');
    }

    $client = new \GuzzleHttp\Client();

    try {
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

        return json_decode($response->getBody(), true);
    } catch (\Exception $e) {
        return null;
    }
}

// Render the flight data dynamically as an HTML table.
function render_dynamic_flight_table($flights)
{
    if (empty($flights) || !isset($flights['data']) || empty($flights['data'])) {
        return '<p>No flights available for the selected criteria.</p>';
    }

    // Extract the keys from the first flight record to generate dynamic headers.
    $first_flight = $flights['data'][0];
    $headers = array_keys($first_flight);

    $html = '<div style="overflow-x:auto;">';
    $html .= '<table class="widefat fixed striped" style="border-collapse: collapse; width: 100%; text-align: left; border: 1px solid #ddd;">';

    // Render table header
    $html .= '<thead><tr style="background-color: #f2f2f2;">';
    foreach ($headers as $header) {
        $html .= '<th style="padding: 10px; text-align: left; border: 1px solid #ddd;">' . esc_html(ucwords(str_replace('_', ' ', $header))) . '</th>';
    }
    $html .= '</tr></thead>';

    // Render table body
    $html .= '<tbody>';
    foreach ($flights['data'] as $flight) {
        $html .= '<tr style="border-bottom: 1px solid #ddd;">';
        foreach ($headers as $header) {
            $value = is_array($flight[$header]) ? implode(', ', $flight[$header]) : ($flight[$header] ?? 'N/A');
            $html .= '<td style="padding: 10px; word-wrap: break-word; max-width: 200px; border: 1px solid #ddd;">' . esc_html($value) . '</td>';
        }
        $html .= '</tr>';
    }
    $html .= '</tbody>';

    $html .= '</table></div>';

    return $html;
}
