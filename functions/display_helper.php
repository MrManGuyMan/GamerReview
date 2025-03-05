<?php
/**
 * Display helper functions for UI components
 */

/**
 * Generate pagination HTML
 *
 * @param int $current_page Current page number
 * @param int $total_pages Total number of pages
 * @param string $game_filter Game filter parameter
 * @param int $rating_filter Rating filter parameter
 * @return string HTML for pagination controls
 */
function generatePagination($current_page, $total_pages, $game_filter, $rating_filter) {
    $html = '<div class="pagination">';

    // Previous page button
    if ($current_page > 1) {
        $html .= '<a href="' . buildPageUrl($current_page - 1, $game_filter, $rating_filter) . '" class="pagination-btn"><i class="fas fa-chevron-left"></i> Previous</a>';
    }

    // Define how many page numbers to show
    $max_visible_pages = 5;
    $start_page = max(1, min($current_page - floor($max_visible_pages / 2), $total_pages - $max_visible_pages + 1));
    $end_page = min($start_page + $max_visible_pages - 1, $total_pages);
    $start_page = max(1, $end_page - $max_visible_pages + 1);

    // First page link if not visible in the range
    if ($start_page > 1) {
        $html .= '<a href="' . buildPageUrl(1, $game_filter, $rating_filter) . '" class="pagination-btn">1</a>';

        if ($start_page > 2) {
            $html .= '<span class="pagination-ellipsis">...</span>';
        }
    }

    // Page numbers
    for ($i = $start_page; $i <= $end_page; $i++) {
        $active_class = ($i === $current_page) ? ' active' : '';
        $html .= '<a href="' . buildPageUrl($i, $game_filter, $rating_filter) . '" class="pagination-btn' . $active_class . '">' . $i . '</a>';
    }

    // Last page link if not visible in the range
    if ($end_page < $total_pages) {
        if ($end_page < $total_pages - 1) {
            $html .= '<span class="pagination-ellipsis">...</span>';
        }

        $html .= '<a href="' . buildPageUrl($total_pages, $game_filter, $rating_filter) . '" class="pagination-btn">' . $total_pages . '</a>';
    }

    // Next page button
    if ($current_page < $total_pages) {
        $html .= '<a href="' . buildPageUrl($current_page + 1, $game_filter, $rating_filter) . '" class="pagination-btn">Next <i class="fas fa-chevron-right"></i></a>';
    }

    $html .= '</div>';

    return $html;
}

/**
 * Build page URL with query parameters
 *
 * @param int $page Page number
 * @param string $game_filter Game filter parameter
 * @param int $rating_filter Rating filter parameter
 * @return string URL with query parameters
 */
function buildPageUrl($page, $game_filter, $rating_filter) {
    $url = '?page=' . $page;

    if (!empty($game_filter)) {
        $url .= '&game=' . urlencode($game_filter);
    }

    if ($rating_filter > 0) {
        $url .= '&rating=' . $rating_filter;
    }

    return $url;
}