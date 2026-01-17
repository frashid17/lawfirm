<?php
/**
 * CLIENT - EVENTS CALENDAR
 * Calendar view of events for client's cases
 */

require_once '../config/database.php';
require_once '../config/session.php';
requireRole('client');

$client_id = $_SESSION['client_id'];

// Get current month/year or from query
$month = isset($_GET['month']) ? (int)$_GET['month'] : date('n');
$year = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$selected_day = isset($_GET['day']) ? (int)$_GET['day'] : null;

// Validate month/year
if ($month < 1 || $month > 12) $month = date('n');
if ($year < 2020 || $year > 2100) $year = date('Y');

// Get first day of month and number of days
$first_day = mktime(0, 0, 0, $month, 1, $year);
$days_in_month = date('t', $first_day);
$day_of_week = date('w', $first_day); // 0 = Sunday

// Get events for this month for client's cases
try {
    $start_date = date('Y-m-01', $first_day);
    $end_date = date('Y-m-t', $first_day);
    $stmt = $conn->prepare("SELECT e.*, c.CaseName 
                           FROM EVENT e 
                           JOIN `CASE` c ON e.CaseNo = c.CaseNo 
                           WHERE c.ClientId = ? 
                           AND DATE(e.Date) >= ? AND DATE(e.Date) <= ?
                           ORDER BY e.Date ASC");
    $stmt->execute([$client_id, $start_date, $end_date]);
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Organize events by day
    $events_by_day = [];
    foreach ($events as $event) {
        $day = date('j', strtotime($event['Date']));
        if (!isset($events_by_day[$day])) {
            $events_by_day[$day] = [];
        }
        $events_by_day[$day][] = $event;
    }
    
    // Get events for selected day
    $selected_day_events = [];
    if ($selected_day && isset($events_by_day[$selected_day])) {
        $selected_day_events = $events_by_day[$selected_day];
    }
} catch(PDOException $e) {
    $error = "Error loading events: " . $e->getMessage();
}

// Navigation
$prev_month = $month - 1;
$prev_year = $year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}

$next_month = $month + 1;
$next_year = $year;
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}

$month_names = ['January', 'February', 'March', 'April', 'May', 'June', 
                'July', 'August', 'September', 'October', 'November', 'December'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events Calendar - Client Portal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .calendar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding: 15px;
            background: white;
            border-radius: 8px;
            box-shadow: var(--shadow-light);
        }
        .calendar-nav {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .calendar-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
        }
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background: var(--border);
            border: 1px solid var(--border);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: var(--shadow-light);
        }
        .calendar-day-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 12px;
            text-align: center;
            font-weight: 600;
            font-size: 14px;
        }
        .calendar-day {
            background: white;
            min-height: 120px;
            padding: 8px;
            position: relative;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid #e9ecef;
        }
        .calendar-day:hover {
            background: #f8f9fa;
            transform: translateY(-2px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .calendar-day.other-month {
            background: #f8f9fa;
            color: #adb5bd;
            cursor: default;
            border: 1px solid #e9ecef;
        }
        .calendar-day.other-month:hover {
            background: #f8f9fa;
            transform: none;
            box-shadow: none;
        }
        .calendar-day.today {
            background: rgba(139, 92, 246, 0.1);
            border: 2px solid var(--primary-color);
        }
        .calendar-day.has-events {
            background: rgba(139, 92, 246, 0.05);
        }
        .day-number {
            font-weight: 600;
            margin-bottom: 6px;
            font-size: 14px;
            color: var(--dark-text);
        }
        .event-badge {
            background: var(--primary-color);
            color: white;
            padding: 4px 8px;
            margin: 3px 0;
            border-radius: 6px;
            font-size: 11px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: block;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .event-badge:hover {
            background: var(--secondary-color);
            transform: translateX(2px);
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
        }
        .event-count {
            background: var(--secondary-color);
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 10px;
            font-weight: 600;
            margin-top: 4px;
            display: inline-block;
        }
        .events-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 10000;
            padding: 20px;
            overflow-y: auto;
        }
        .events-modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .events-modal-content {
            background: white;
            border-radius: 12px;
            padding: 25px;
            max-width: 600px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
        }
        .events-modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid var(--border);
        }
        .events-modal-title {
            font-size: 20px;
            font-weight: 600;
            color: var(--primary-color);
            margin: 0;
        }
        .close-modal {
            background: none;
            border: none;
            font-size: 24px;
            color: var(--gray);
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s;
        }
        .close-modal:hover {
            background: var(--light-gray-bg);
            color: var(--dark-text);
        }
        .event-detail {
            padding: 15px;
            margin-bottom: 15px;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }
        .event-detail:hover {
            background: #e9ecef;
            transform: translateX(4px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .event-detail h4 {
            margin: 0 0 10px 0;
            color: var(--primary-color);
            font-size: 18px;
        }
        .event-detail-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 10px;
            font-size: 14px;
            color: var(--gray);
        }
        .event-detail-meta span {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .event-detail-meta i {
            color: var(--primary-color);
        }
        .event-detail-description {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid var(--border);
            color: var(--dark-text);
            line-height: 1.6;
        }
        .no-events {
            text-align: center;
            padding: 40px;
            color: var(--gray);
        }
        .no-events i {
            font-size: 48px;
            margin-bottom: 15px;
            opacity: 0.5;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1>Events Calendar - Client Portal</h1>
            <div class="header-user">
                <a href="dashboard.php" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
                <a href="logout.php" class="btn btn-secondary btn-sm"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </div>
        </div>
    </div>
    
    <div class="nav">
        <div class="nav-content">
            <ul class="nav-menu">
                <li><a href="dashboard.php"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="my_cases.php"><i class="fas fa-folder-open"></i> My Cases</a></li>
                <li><a href="invoices.php"><i class="fas fa-file-invoice-dollar"></i> Invoices</a></li>
                <li><a href="payment_history.php"><i class="fas fa-history"></i> Payment History</a></li>
                <li><a href="documents.php"><i class="fas fa-file"></i> Documents</a></li>
                <li><a href="messages.php"><i class="fas fa-comments"></i> Messages</a></li>
                <li><a href="events.php" class="active"><i class="fas fa-calendar-alt"></i> Events</a></li>
            </ul>
        </div>
    </div>
    
    <div class="container">
        <?php if (isset($error)): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <div class="calendar-header">
            <div class="calendar-nav">
                <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" class="btn btn-secondary">
                    <i class="fas fa-chevron-left"></i> Previous
                </a>
                <h2 class="calendar-title"><?php echo $month_names[$month - 1] . ' ' . $year; ?></h2>
                <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" class="btn btn-secondary">
                    Next <i class="fas fa-chevron-right"></i>
                </a>
            </div>
            <a href="events.php" class="btn btn-primary">
                <i class="fas fa-calendar-day"></i> Today
            </a>
        </div>
        
        <div class="calendar-grid">
            <?php
            $day_names = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            foreach ($day_names as $day_name): ?>
                <div class="calendar-day-header"><?php echo $day_name; ?></div>
            <?php endforeach; ?>
            
            <?php
            // Fill empty days before month starts
            for ($i = 0; $i < $day_of_week; $i++): ?>
                <div class="calendar-day other-month"></div>
            <?php endfor; ?>
            
            <?php
            // Days of the month
            $today = date('Y-m-d');
            for ($day = 1; $day <= $days_in_month; $day++):
                $current_date = date('Y-m-d', mktime(0, 0, 0, $month, $day, $year));
                $is_today = ($current_date === $today);
                $day_events = $events_by_day[$day] ?? [];
                $has_events = count($day_events) > 0;
            ?>
                <div class="calendar-day <?php echo $is_today ? 'today' : ''; ?> <?php echo $has_events ? 'has-events' : ''; ?>" 
                     onclick="showDayEvents(<?php echo $day; ?>, <?php echo $month; ?>, <?php echo $year; ?>)">
                    <div class="day-number"><?php echo $day; ?></div>
                    <?php if ($has_events): ?>
                        <?php foreach (array_slice($day_events, 0, 2) as $event): ?>
                            <div class="event-badge" 
                                 onclick="event.stopPropagation(); showEventDetails(<?php echo htmlspecialchars(json_encode($event)); ?>)" 
                                 title="<?php echo htmlspecialchars($event['EventName']); ?>">
                                <?php echo htmlspecialchars(substr($event['EventName'], 0, 20)); ?>
                            </div>
                        <?php endforeach; ?>
                        <?php if (count($day_events) > 2): ?>
                            <div class="event-count">+<?php echo count($day_events) - 2; ?> more</div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
            
            <?php
            // Fill remaining days
            $total_cells = 42; // 6 weeks * 7 days
            $filled_cells = $day_of_week + $days_in_month;
            $remaining = $total_cells - $filled_cells;
            for ($i = 0; $i < $remaining; $i++): ?>
                <div class="calendar-day other-month"></div>
            <?php endfor; ?>
        </div>
    </div>
    
    <!-- Events Modal -->
    <div id="eventsModal" class="events-modal" onclick="closeModalOnOutside(event)">
        <div class="events-modal-content" onclick="event.stopPropagation();">
            <div class="events-modal-header">
                <h3 class="events-modal-title" id="modalTitle">Events</h3>
                <button class="close-modal" onclick="closeEventsModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="modalEventsContent">
                <!-- Events will be loaded here -->
            </div>
        </div>
    </div>
    
    <script>
        // Store events data
        const eventsData = <?php echo json_encode($events_by_day); ?>;
        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 
                          'July', 'August', 'September', 'October', 'November', 'December'];
        
        function showDayEvents(day, month, year) {
            const modal = document.getElementById('eventsModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalContent = document.getElementById('modalEventsContent');
            
            modalTitle.textContent = `Events - ${monthNames[month - 1]} ${day}, ${year}`;
            
            const dayEvents = eventsData[day] || [];
            
            if (dayEvents.length === 0) {
                modalContent.innerHTML = `
                    <div class="no-events">
                        <i class="fas fa-calendar-times"></i>
                        <p>No events scheduled for this day</p>
                    </div>
                `;
            } else {
                let html = '';
                dayEvents.forEach(event => {
                    const eventDate = new Date(event.Date);
                    const timeStr = eventDate.toLocaleTimeString('en-US', { 
                        hour: '2-digit', 
                        minute: '2-digit',
                        hour12: true 
                    });
                    
                    html += `
                        <div class="event-detail" onclick="showEventDetails(${escapeHtml(JSON.stringify(event))})" style="cursor: pointer;">
                            <h4>${escapeHtml(event.EventName)}</h4>
                            <div class="event-detail-meta">
                                <span><i class="fas fa-tag"></i> ${escapeHtml(event.EventType)}</span>
                                <span><i class="fas fa-clock"></i> ${timeStr}</span>
                                ${event.Location ? `<span><i class="fas fa-map-marker-alt"></i> ${escapeHtml(event.Location)}</span>` : ''}
                                <span><i class="fas fa-folder"></i> ${escapeHtml(event.CaseName)}</span>
                            </div>
                            ${event.Description ? `<div class="event-detail-description">${escapeHtml(event.Description)}</div>` : ''}
                        </div>
                    `;
                });
                modalContent.innerHTML = html;
            }
            
            modal.classList.add('active');
        }
        
        function showEventDetails(event) {
            const modal = document.getElementById('eventsModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalContent = document.getElementById('modalEventsContent');
            
            const eventDate = new Date(event.Date);
            const dateStr = eventDate.toLocaleDateString('en-US', { 
                weekday: 'long',
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
            const timeStr = eventDate.toLocaleTimeString('en-US', { 
                hour: '2-digit', 
                minute: '2-digit',
                hour12: true 
            });
            
            modalTitle.textContent = escapeHtml(event.EventName);
            modalContent.innerHTML = `
                <div class="event-detail" style="cursor: default;">
                    <div class="event-detail-meta" style="margin-bottom: 15px;">
                        <span style="display: block; margin-bottom: 8px; font-size: 16px; font-weight: 600; color: var(--primary-color);">
                            <i class="fas fa-calendar"></i> ${dateStr}
                        </span>
                        <span style="display: block; margin-bottom: 8px; font-size: 15px;">
                            <i class="fas fa-clock"></i> ${timeStr}
                        </span>
                    </div>
                    <div class="event-detail-meta">
                        <span><i class="fas fa-tag"></i> <strong>Type:</strong> ${escapeHtml(event.EventType)}</span>
                        ${event.Location ? `<span><i class="fas fa-map-marker-alt"></i> <strong>Location:</strong> ${escapeHtml(event.Location)}</span>` : ''}
                        <span><i class="fas fa-folder"></i> <strong>Case:</strong> ${escapeHtml(event.CaseName)}</span>
                    </div>
                    ${event.Description ? `
                        <div class="event-detail-description" style="margin-top: 15px; padding-top: 15px; border-top: 2px solid var(--border);">
                            <strong style="display: block; margin-bottom: 8px; color: var(--primary-color);">Description:</strong>
                            <p style="margin: 0; line-height: 1.6;">${escapeHtml(event.Description)}</p>
                        </div>
                    ` : ''}
                </div>
            `;
            
            modal.classList.add('active');
        }
        
        function closeEventsModal() {
            document.getElementById('eventsModal').classList.remove('active');
        }
        
        function closeModalOnOutside(event) {
            if (event.target.id === 'eventsModal') {
                closeEventsModal();
            }
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Close modal on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeEventsModal();
            }
        });
        
        // Show events for selected day if in URL
        <?php if ($selected_day && count($selected_day_events) > 0): ?>
        window.addEventListener('load', function() {
            showDayEvents(<?php echo $selected_day; ?>, <?php echo $month; ?>, <?php echo $year; ?>);
        });
        <?php endif; ?>
    </script>
</body>
</html>
