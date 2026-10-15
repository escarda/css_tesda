<?php
// coc1_review.php - COC 1: Install & Configure Computer Systems
// Android WebView-safe layout/modal + overlay-based brightness (no CSS filter)

require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$coc_number = 1;
$page_title = "COC 1: Install & Configure Computer Systems";

$sql = "
    SELECT rm.id, rm.coc_number, rm.title, rm.content, rm.category
    FROM review_materials rm
    INNER JOIN (
        SELECT MIN(id) AS id
        FROM review_materials
        WHERE coc_number = ?
        GROUP BY LOWER(TRIM(title))
    ) unique_materials ON rm.id = unique_materials.id
    ORDER BY rm.id
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $coc_number);
$stmt->execute();
$materials = $stmt->get_result();
$stmt->close();

$fallback_image = 'https://upload.wikimedia.org/wikipedia/commons/thumb/2/2a/Computer_case_side_open.jpg/500px-Computer_case_side_open.jpg';

// Image rules: first match wins. 'any' = at least one keyword; 'all' = every keyword must appear.
$image_rules = [
    ['any' => ['introduction', 'computer system'], 'url' => 'https://i.pinimg.com/736x/12/3a/c2/123ac257b8eabb6d994f4cd0550637fc.jpg'],
    ['any' => ['cpu', 'central processing'], 'url' => 'https://i.pinimg.com/736x/0e/b7/ca/0eb7cafc92d81777fb781044ad47344f.jpg'],
    ['any' => ['motherboard'], 'url' => 'https://i.pinimg.com/736x/c6/9f/0e/c69f0ec66156d60d9dac1f93adefc027.jpg'],
    ['any' => ['ram', 'memory'], 'url' => 'https://i.pinimg.com/736x/24/ab/b7/24abb73cc7f22175538563921e1046f2.jpg'],
    ['any' => ['storage', 'ssd', 'hdd'], 'url' => 'https://i.pinimg.com/736x/49/a4/60/49a460902979c293fefbc3f1c2e9e54c.jpg'],
    ['any' => ['power supply', 'psu'], 'url' => 'https://i.pinimg.com/736x/a3/46/d4/a346d43616a4031be3d183775284a5be.jpg'],
    ['any' => ['graphics card', 'gpu'], 'url' => 'https://i.pinimg.com/736x/57/20/29/572029b32c23430468db28c8417ec330.jpg'],
    ['any' => ['input device'], 'all' => ['input', 'device'], 'url' => 'https://i.pinimg.com/736x/15/bc/e6/15bce688d582bd0b71748778eab12059.jpg'],
    ['any' => ['output device'], 'all' => ['output', 'device'], 'url' => 'https://i.pinimg.com/736x/b4/7d/a8/b47da8c4448b70e579e32ea8df7ce0c4.jpg'],
    ['any' => ['display interface', 'hdmi', 'vga'], 'url' => 'https://i.pinimg.com/736x/e8/5e/4d/e85e4d1b96c68e3f38f1e59b77a2f104.jpg'],
    ['all' => ['tools', 'assembly'], 'url' => 'https://i.pinimg.com/736x/51/35/f9/5135f901859ebe22b8359830db981360.jpg'],
    ['any' => ['step-by-step'], 'all' => ['assembly', 'guide'], 'url' => 'https://i.pinimg.com/1200x/ab/be/2c/abbe2cc70c004d57a6d1ab51aa368cfe.jpg'],
    ['any' => ['thermal paste'], 'url' => 'https://i.pinimg.com/1200x/c0/90/c9/c090c9f70b0f806d0264d2684188e865.jpg'],
    ['any' => ['cable management'], 'url' => 'https://i.pinimg.com/736x/08/d0/1f/08d01f81336f49fc118172da6be6c945.jpg'],
    ['any' => ['bios vs', 'uefi vs'], 'url' => 'https://i.pinimg.com/736x/c0/ff/61/c0ff615cec08c1887b69c121ddc48c79.jpg'],
    ['any' => ['accessing bios', 'enter bios'], 'url' => 'https://i.pinimg.com/736x/b4/40/5c/b4405c329da3087d27a10803d8389937.jpg'],
    ['any' => ['types of computer'], 'url' => 'https://i.pinimg.com/1200x/4e/e5/5d/4ee55dd9b9fe15cdb7ac62164cfe78b6.jpg'],
    ['any' => ['essential bios', 'bios settings'], 'url' => 'https://r.testifier.nl/Acbs8526SDKI/resizing_type:fit/width:3840/height:2560/plain/https://s3-newsifier.ams3.digitaloceanspaces.com/gizchina.com/images/2025-06/essential-bios-settings.jpg@webp'],
    ['any' => ['updating bios', 'update bios'], 'url' => 'https://benhvientinhoc.com/wp-content/uploads/2025/10/image-469-1024x544.png'],
    ['any' => ['installation media', 'bootable usb'], 'url' => 'https://lh3.googleusercontent.com/vqz-6KM73-u6FXY_BL_dq5BfkOW8BYP7BWI_OQTfJYvDGrFVUoBt5aeXC7hRmO97sTHjViER-2Vh3asnv9Ija9kWmnMdn2bOdJtbE7oPuPCca3ta-n115uFzNORRz2FbLyRcOeZSX9tm0nR7jNpxCi4'],
    ['any' => ['windows 10', 'windows 11', 'windows installation'], 'url' => 'https://i.pinimg.com/736x/8c/a0/ae/8ca0aedb02a6981e3e9e5878cbcc10dc.jpg'],
    ['any' => ['linux', 'ubuntu'], 'url' => 'https://i.pinimg.com/1200x/0b/ad/34/0bad343bbffdb73abf4e308f499b31c6.jpg'],
    ['any' => ['disk partition', 'partitioning'], 'url' => 'https://www.nirmaltv.com/images/Partition-hard-disk-in-windows-11-1536x919.png'],
    ['any' => ['device driver'], 'url' => 'https://www.jengal.com/blog/wp-content/uploads/2022/08/examples-of-device-drivers.jpg'],
    ['any' => ['installing driver'], 'url' => 'https://images.drivereasy.com/wp-content/uploads/2016/10/img_5805d0cfb5bda.png'],
    ['any' => ['essential software'], 'url' => 'https://windows-cdn.softpedia.com/screenshots/Essential-Software-Installer_1.png'],
    ['any' => ['network connection', 'wired connection'], 'url' => 'https://www.guidingtech.com/wp-content/uploads/open-network-connections-using-controlpanel4.png'],
    ['any' => ['ip configuration', 'static ip'], 'url' => 'https://www.sas.upenn.edu/~jasonrw/images/Network_000.jpg'],
    ['any' => ['network command', 'ping'], 'url' => 'https://cdn.mos.cms.futurecdn.net/znE7aCVfgeDVxDz2vNVWub-1024-80.jpg'],
    ['any' => ['esd', 'electrostatic'], 'url' => 'https://i0.wp.com/blog.minicircuits.com/wp-content/uploads/2019/12/ESD-Saftey.jpg?resize=2048%2C1296&ssl=1'],
    ['any' => ['electrical safety'], 'url' => 'https://greenwgroup.co.in/wp-content/uploads/2015/03/Electrical-Safety-1-1.jpg'],
    ['any' => ['physical safety'], 'url' => 'https://th.bing.com/th/id/R.858814a868c5a4e6789504937792a6c3?rik=iaEJSMx4N1ykIw&riu=http%3a%2f%2fwww.anatomystuff.co.uk%2fcdn%2fshop%2ffiles%2ftop-tips-for-safe-lifting-poster-product-image.jpg%3fv%3d1741093901&ehk=O4oV3mrkN503tj9tS%2fgcAy6Q63xuGc%2ft%2fto9lRJgJiI%3d&risl=&pid=ImgRaw&r=0'],
    ['any' => ["won't boot", 'no power'], 'url' => 'https://www.stellarinfo.co.in/blog/wp-content/uploads/2023/04/Windows-10-Wont-Boot-on-My-PC-002.jpg'],
    ['any' => ['no display', 'no signal'], 'url' => 'https://tse1.mm.bing.net/th/id/OIP.pRo0wK7OdA16BxXRd4R1vAHaTR?rs=1&pid=ImgDetMain&o=7&rm=3'],
    ['any' => ['blue screen', 'bsod'], 'url' => 'https://us.norton.com/content/dam/blogs/images/norton/am/blue-screen-of-death-01.jpg'],
    ['any' => ['overheating'], 'url' => 'https://t3.ftcdn.net/jpg/06/27/96/94/360_F_627969437_S5KcQIH15f1pk8BtSFxzhydorCKMqtmU.jpg'],
    ['any' => ['slow performance'], 'url' => 'https://image.slidesharecdn.com/howtofixlaptopslowperformance-230809081012-a5837647/75/How-To-Fix-Laptop-Slow-Performance-1-2048.jpg'],
    ['any' => ['no internet'], 'url' => 'https://www.systweak.com/blogs/wp-content/uploads/2024/09/How-to-Fix-No-Internet-Access-while-using-Ethernet-Connection.webp'],
    ['all' => ['cleaning', 'component'], 'url' => 'https://thumbs.dreamstime.com/z/computer-repair-cleaning-components-dust-your-221487401.jpg'],
    ['any' => ['software maintenance'], 'url' => 'https://distantjob.com/wp-content/uploads/2021/11/Ways-to-Improve-Software-Maintenance.jpg'],
    ['any' => ['backup'], 'url' => 'https://www.collidu.com/media/catalog/product/img/f/e/fee0512a55bb20f7e4c6901e8d070abb46266b0aeaaef20b2a64886e8279a988/data-backup-strategy-slide2.png'],
    ['any' => ['performance monitoring'], 'url' => 'https://image3.slideserve.com/5405856/performance-monitoring-approaches-l.jpg'],
    ['all' => ['inventory', 'documentation'], 'url' => 'https://www.gep.com/prod/s3fs-public/images/Inventory-Management-Software-Features-Must-Have.jpg'],
    ['any' => ['troubleshooting documentation'], 'url' => 'https://document360.com/wp-content/uploads/2023/01/Microsofts_troubleshooting_guide-768x368.png'],
    ['any' => ['system configuration'], 'url' => 'https://image.slideserve.com/1275638/documenting-a-linux-system-configuration-l.jpg'],
];

function coc1_pick_image($title_lower, $rules, $fallback) {
    foreach ($rules as $rule) {
        $hit = false;
        if (!empty($rule['any'])) {
            foreach ($rule['any'] as $kw) {
                if (strpos($title_lower, $kw) !== false) { $hit = true; break; }
            }
        }
        if (!$hit && !empty($rule['all'])) {
            $hit = true;
            foreach ($rule['all'] as $kw) {
                if (strpos($title_lower, $kw) === false) { $hit = false; break; }
            }
        }
        if ($hit) return $rule['url'];
    }
    return $fallback;
}

$category_icons = [
    'hardware' => 'microchip', 'safety' => 'shield-alt', 'tools' => 'wrench',
    'assembly' => 'tools', 'bios' => 'desktop', 'networking' => 'network-wired',
    'software' => 'code', 'documentation' => 'file-alt',
];

$initial_brightness = (int)(function_exists('getGlobalBrightness') ? getGlobalBrightness() : 100);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <?php if (file_exists(__DIR__ . '/includes/theme_preload.php')) { include __DIR__ . '/includes/theme_preload.php'; } ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title><?php echo htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8'); ?> | CSS TESDA Reviewer</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <?php if (file_exists(__DIR__ . '/includes/theme_vars.php')) { include __DIR__ . '/includes/theme_vars.php'; } ?>
    <style>
        :root {
            --bg-primary: #050812;
            --bg-navbar: rgba(5, 8, 18, 0.92);
            --bg-card: rgba(10, 20, 40, 0.82);
            --bg-panel: rgba(10, 20, 40, 0.95);
            --bg-stat: rgba(0, 255, 157, 0.08);
            --text-primary: #e8fff6;
            --text-secondary: #9db3ad;
            --border-light: rgba(0, 255, 157, 0.18);
            --border-color: rgba(0, 255, 157, 0.35);
            --cyber-bg1: #050812;
            --cyber-bg2: #071a1a;
            --grid-color: rgba(0, 255, 157, 0.035);
        }

        html[data-theme="light"] {
            --bg-primary: #f4f8f7;
            --bg-navbar: rgba(255, 255, 255, 0.94);
            --bg-card: rgba(255, 255, 255, 0.96);
            --bg-panel: rgba(255, 255, 255, 0.98);
            --bg-stat: rgba(0, 150, 100, 0.08);
            --text-primary: #17231f;
            --text-secondary: #53645e;
            --border-light: rgba(0, 130, 90, 0.18);
            --border-color: rgba(0, 130, 90, 0.32);
            --cyber-bg1: #eef8f4;
            --cyber-bg2: #e7f3ff;
            --grid-color: rgba(0, 120, 90, 0.06);
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        /* Never allow a CSS filter on the page roots: it breaks position:fixed (modal) in WebViews.
           Brightness is handled by #brightnessOverlay instead. */
        html, body { overflow-x: hidden; filter: none !important; }

        body {
            font-family: 'Rajdhani', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            min-height: 100vh;
            transition: background 0.3s ease, color 0.3s ease;
            position: relative;
        }

        html.modal-open, body.modal-open { overflow: hidden !important; touch-action: none; }

        /* ---------- Brightness overlay ---------- */
        #brightnessOverlay {
            position: fixed;
            top: 0; right: 0; bottom: 0; left: 0;
            background: #000;
            opacity: 0;               /* 0 = full brightness */
            pointer-events: none;     /* never blocks taps */
            z-index: 2147483100;      /* above modal (2147483000) */
            transition: opacity 0.15s ease;
        }

        .cyber-bg {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            z-index: -2;
            background: linear-gradient(135deg, var(--cyber-bg1) 0%, var(--cyber-bg2) 100%);
        }

        .grid-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background-image: linear-gradient(var(--grid-color) 1px, transparent 1px), linear-gradient(90deg, var(--grid-color) 1px, transparent 1px);
            background-size: 50px 50px;
            z-index: -1;
            animation: gridMove 20s linear infinite;
        }

        @keyframes gridMove {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        .navbar {
            background: var(--bg-navbar);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-light);
            padding: 1rem 2rem;
            position: sticky;
            top: 0;
            z-index: 100;
            transition: background 0.3s ease;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .logo {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.5rem;
            font-weight: 800;
            background: linear-gradient(135deg, #00ff9d, #0066ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
        }

        .nav-links { display: flex; gap: 1.5rem; align-items: center; flex-wrap: wrap; }

        .nav-links a {
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-links a:hover, .nav-links a.active { color: #00ff9d; }

        .user-info { display: flex; align-items: center; gap: 1rem; color: #00ff9d; }

        .theme-toggle-nav {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            padding: 8px 13px;
            border: 1px solid rgba(0, 255, 157, 0.45);
            border-radius: 10px;
            background: rgba(0, 255, 157, 0.08);
            color: var(--text-primary);
            font-family: 'Rajdhani', sans-serif;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
            white-space: nowrap;
        }

        .theme-toggle-nav:hover {
            color: #00ff9d;
            border-color: #00ff9d;
            background: rgba(0, 255, 157, 0.15);
            transform: translateY(-1px);
        }

        .theme-toggle-nav i { font-size: 0.95rem; }

        .container { max-width: 1400px; margin: 0 auto; padding: 2rem; }

        .page-header { text-align: center; margin-bottom: 3rem; }

        .page-header h1 {
            font-family: 'Orbitron', sans-serif;
            font-size: 2.5rem;
            background: linear-gradient(135deg, #00ff9d, #0066ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 1rem;
        }

        .page-header p { color: var(--text-secondary); font-size: 1.1rem; }

        .coc-badge {
            display: inline-block;
            padding: 8px 20px;
            background: rgba(0, 255, 157, 0.1);
            border: 1px solid #00ff9d;
            border-radius: 50px;
            color: #00ff9d;
            margin-bottom: 1rem;
        }

        .key-topics {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: var(--bg-card);
            border: 1px solid var(--border-light);
            border-radius: 16px;
        }

        .key-topics h3 { color: #00ff9d; margin-bottom: 1rem; display: flex; align-items: center; gap: 10px; }

        .topic-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(min(250px, 100%), 1fr));
            gap: 0.8rem;
            list-style: none;
        }

        .topic-list li {
            padding: 0.5rem 1rem;
            background: var(--bg-stat);
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-primary);
        }

        .topic-list li i { color: #00ff9d; }

        .materials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(min(380px, 100%), 1fr));
            gap: 1.5rem;
        }

        .material-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            overflow: hidden;
            transition: all 0.3s;
            cursor: pointer;
            position: relative;
            -webkit-tap-highlight-color: transparent;
            touch-action: manipulation;
        }

        .material-card:hover {
            transform: translateY(-8px);
            border-color: #00ff9d;
            box-shadow: 0 15px 40px rgba(0, 255, 157, 0.2);
        }

        .material-card:active { transform: translateY(-4px); }

        .material-card::after {
            content: '\f054';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            bottom: 15px;
            right: 15px;
            color: #00ff9d;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .material-card:hover::after { opacity: 1; }

        .card-image {
            width: 100%;
            height: 280px;
            overflow: hidden;
            background: linear-gradient(135deg, #1a1f2e, #0a0f1e);
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .card-image img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            transition: transform 0.5s ease;
            background: #0a0f1e;
        }

        .material-card:hover .card-image img { transform: scale(1.1); }

        .card-header {
            padding: 1rem 1.5rem;
            background: rgba(0, 255, 157, 0.05);
            border-bottom: 1px solid rgba(0, 255, 157, 0.1);
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .card-header i { font-size: 1.5rem; color: #00ff9d; }
        .card-header h3 { font-family: 'Orbitron', sans-serif; font-size: 1.1rem; color: #00ff9d; }

        .card-body { padding: 1.5rem; }
        .card-body p { color: var(--text-secondary); line-height: 1.6; margin-bottom: 0.5rem; }

        .category-tag {
            display: inline-block;
            padding: 4px 12px;
            background: rgba(0, 255, 157, 0.1);
            border-radius: 20px;
            font-size: 0.7rem;
            color: #00ff9d;
            margin-bottom: 1rem;
        }

        .read-more {
            color: #00ff9d;
            font-size: 0.85rem;
            margin-top: 10px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        html[data-theme="light"] .navbar { box-shadow: 0 2px 18px rgba(0, 0, 0, 0.08); }
        html[data-theme="light"] .material-card,
        html[data-theme="light"] .quiz-section { color: var(--text-primary); }
        html[data-theme="light"] .theme-toggle-nav {
            color: #17231f;
            background: rgba(0, 150, 100, 0.08);
            border-color: rgba(0, 130, 90, 0.32);
        }

        .quiz-section {
            margin-top: 3rem;
            padding: 2rem;
            background: var(--bg-card);
            border: 1px solid var(--border-light);
            border-radius: 20px;
            text-align: center;
        }

        .quiz-section h2 { color: #00ff9d; margin-bottom: 1rem; }
        .quiz-section p { color: var(--text-secondary); }

        .btn {
            padding: 10px 24px;
            border: none;
            border-radius: 50px;
            font-family: 'Orbitron', sans-serif;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary { background: linear-gradient(45deg, #00ff9d, #0066ff); color: #050812; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0, 255, 157, 0.4); }
        .btn-secondary { background: transparent; border: 2px solid #00ff9d; color: #00ff9d; }
        .btn-secondary:hover { background: #00ff9d; color: #050812; }

        .quiz-buttons { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin-top: 1.5rem; }

        /* ---------- MODAL - ANDROID WEBVIEW SAFE ---------- */
        .modal {
            display: none;
            position: fixed !important;
            top: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            left: 0 !important;
            width: 100% !important;
            height: 100% !important;
            min-height: 0 !important;
            margin: 0 !important;
            padding: 10px !important;
            background: rgba(5, 8, 18, 0.88);
            z-index: 2147483000 !important;
            overflow: hidden !important;
            box-sizing: border-box;
            transform: none !important;
            align-items: center;
            justify-content: center;
        }

        .modal.active { display: flex !important; }

        .modal-content {
            width: calc(100% - 20px);
            max-width: 780px;
            height: calc(100% - 20px);
            max-height: 900px;
            margin: 0 auto;
            background: var(--bg-panel, #0a1428);
            border: 2px solid #00ff9d;
            border-radius: 18px;
            box-shadow: 0 25px 80px rgba(0, 255, 157, 0.22);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            position: relative;
            box-sizing: border-box;
        }

        .modal-header {
            flex: 0 0 auto;
            min-height: 58px;
            padding: 0.85rem 1rem;
            border-bottom: 1px solid rgba(0, 255, 157, 0.2);
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }

        .modal-header h2 {
            font-family: 'Orbitron', sans-serif;
            color: #00ff9d;
            font-size: 1.25rem;
            line-height: 1.3;
            overflow-wrap: anywhere;
            word-break: break-word;
            min-width: 0;
            flex: 1;
        }

        .close-modal {
            flex: 0 0 auto;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: transparent;
            border: 0;
            color: #8899cc;
            font-size: 1.8rem;
            line-height: 1;
            cursor: pointer;
            -webkit-tap-highlight-color: transparent;
        }

        .close-modal:hover, .close-modal:active { color: #ef4444; }

        .image-container {
            position: relative;
            flex: 0 1 42%;
            min-height: 190px;
            max-height: 46%;
            background: #0a0f1e;
            padding: 0.5rem;
            box-sizing: border-box;
            overflow: hidden;
        }

        .zoom-controls {
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 6px;
            z-index: 20;
            background: rgba(0, 0, 0, 0.78);
            padding: 5px 7px;
            border-radius: 40px;
            border: 1px solid rgba(0, 255, 157, 0.3);
            white-space: nowrap;
        }

        .zoom-btn {
            background: rgba(0, 255, 157, 0.2);
            border: none;
            color: #00ff9d;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.15rem;
            font-weight: bold;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            -webkit-tap-highlight-color: transparent;
        }

        .zoom-reset { background: rgba(255, 165, 0, 0.2); color: #ffaa00; }

        .zoom-level {
            color: #00ff9d;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            padding: 0 5px;
            font-family: monospace;
        }

        .modal-image {
            width: 100%;
            height: 100%;
            overflow: auto;
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
            background: #0a0f1e;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: default;
            position: relative;
            box-sizing: border-box;
        }

        .modal-image img {
            display: block;
            width: auto;
            height: auto;
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
            transform-origin: center center;
            transition: transform 0.1s ease;
            pointer-events: none;
            flex: 0 0 auto;
        }

        .pan-hint {
            position: absolute;
            bottom: 62px;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(0, 0, 0, 0.55);
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 0.65rem;
            color: #8899cc;
            pointer-events: none;
            white-space: nowrap;
            z-index: 15;
        }

        .modal-body {
            flex: 1 1 auto;
            min-height: 0;
            padding: 1rem 1.15rem;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
            box-sizing: border-box;
        }

        .modal-body p {
            color: #cbd5e1;
            line-height: 1.65;
            margin: 0;
            white-space: pre-line;
            overflow-wrap: anywhere;
            word-break: normal;
        }

        html[data-theme="light"] .modal-body p { color: #33433d; }

        .modal-category {
            display: inline-block;
            padding: 4px 12px;
            background: rgba(0, 255, 157, 0.1);
            border-radius: 20px;
            font-size: 0.8rem;
            color: #00ff9d;
            margin-bottom: 0.75rem;
        }

        @media (max-width: 768px) {
            .navbar { padding: 0.8rem 1rem; }
            .nav-links { width: 100%; justify-content: center; gap: 1rem; }
            .theme-toggle-nav { padding: 7px 10px; }
            .container { padding: 1rem; }
            .page-header h1 { font-size: 1.8rem; }
            .materials-grid { grid-template-columns: 1fr; }
            .card-image { height: 220px; }

            .modal { padding: 7px !important; }
            .modal-content { width: calc(100% - 4px); height: calc(100% - 4px); max-height: none; border-radius: 15px; }
            .modal-header { min-height: 54px; padding: 0.65rem 0.75rem; }
            .modal-header h2 { font-size: 0.92rem; line-height: 1.25; }
            .close-modal { width: 36px; height: 36px; font-size: 1.55rem; }

            .image-container { flex: 0 0 38%; height: 38%; min-height: 175px; max-height: 38%; padding: 0.35rem; }
            .modal-image { height: 100%; }
            .modal-image img { max-width: 100%; max-height: 100%; }

            .modal-body { flex: 1 1 auto; padding: 0.8rem 0.9rem 1rem; }
            .modal-body p { font-size: 0.9rem; line-height: 1.5; }
            .modal-category { font-size: 0.72rem; padding: 3px 9px; margin-bottom: 0.55rem; }

            .zoom-controls { bottom: 7px; }
            .zoom-btn { width: 31px; height: 31px; font-size: 1rem; }
            .zoom-level { font-size: 0.68rem; padding: 0 3px; }
            .pan-hint { display: none; }
        }

        @media (max-width: 768px) and (orientation: landscape) {
            .modal-content { width: calc(100% - 8px); height: calc(100% - 8px); }
            .modal-header { min-height: 46px; padding: 0.45rem 0.7rem; }
            .modal-header h2 { font-size: 0.85rem; }
            .image-container { flex: 0 0 45%; height: 45%; max-height: 45%; min-height: 130px; }
            .modal-body { padding: 0.6rem 0.8rem; }
        }
    </style>
</head>
<body>
    <div id="brightnessOverlay"></div>
    <div class="cyber-bg"></div>
    <div class="grid-overlay"></div>

    <nav class="navbar">
        <div class="nav-container">
            <a href="dashboard.php" class="logo">CSS TESDA Reviewer</a>
            <div class="nav-links">
                <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="coc1_review.php" class="active"><i class="fas fa-microchip"></i> COC 1</a>
                <a href="coc2_review.php"><i class="fas fa-network-wired"></i> COC 2</a>
                <a href="coc3_review.php"><i class="fas fa-server"></i> COC 3</a>
                <a href="coc4_review.php"><i class="fas fa-tools"></i> COC 4</a>

                <div class="user-info">
                    <i class="fas fa-user"></i>
                    <span><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></span>
                    <button type="button" class="theme-toggle-nav" id="themeToggleNav" aria-label="Switch theme" title="Switch between dark mode and light mode">
                        <i class="fas fa-sun" id="themeToggleIcon"></i>
                        <span id="themeToggleText">Light Mode</span>
                    </button>
                    <a href="logout.php" style="color: #ef4444;"><i class="fas fa-sign-out-alt"></i></a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="page-header">
            <div class="coc-badge">
                <i class="fas fa-microchip"></i> COC 1: Install &amp; Configure Computer Systems
            </div>
            <h1>Complete Review Materials</h1>
            <p>Click on any card to view detailed information. Use + / - buttons or mouse wheel to zoom images.</p>
        </div>

        <div class="key-topics">
            <h3><i class="fas fa-star-of-life"></i> All Topics Covered</h3>
            <ul class="topic-list">
                <li><i class="fas fa-desktop"></i> Introduction to Computer Systems</li>
                <li><i class="fas fa-microchip"></i> CPU, Motherboard, RAM</li>
                <li><i class="fas fa-sd-card"></i> Storage (SSD vs HDD)</li>
                <li><i class="fas fa-plug"></i> Power Supply Unit (PSU)</li>
                <li><i class="fas fa-tv"></i> Graphics Card (GPU)</li>
                <li><i class="fas fa-keyboard"></i> Input &amp; Output Devices</li>
                <li><i class="fas fa-tools"></i> Assembly &amp; Cable Management</li>
                <li><i class="fas fa-desktop"></i> BIOS/UEFI Configuration</li>
                <li><i class="fas fa-window-maximize"></i> OS Installation (Windows/Linux)</li>
                <li><i class="fas fa-network-wired"></i> Network Setup &amp; Commands</li>
                <li><i class="fas fa-shield-alt"></i> Safety &amp; ESD Protection</li>
                <li><i class="fas fa-search"></i> Troubleshooting (BSOD, No Boot, etc.)</li>
                <li><i class="fas fa-chart-line"></i> Maintenance &amp; Backup Strategies</li>
                <li><i class="fas fa-file-alt"></i> Documentation &amp; Inventory</li>
            </ul>
        </div>

        <div class="materials-grid">
            <?php if ($materials->num_rows > 0): ?>
                <?php while ($material = $materials->fetch_assoc()):
                    $title_lower = strtolower(trim($material['title']));
                    $image_url = coc1_pick_image($title_lower, $image_rules, $fallback_image);
                    $category = strtolower(trim((string)$material['category']));
                    $icon = $category_icons[$category] ?? 'book';
                ?>
                    <div class="material-card" data-material='<?php echo htmlspecialchars(json_encode($material, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, "UTF-8"); ?>' data-image='<?php echo htmlspecialchars($image_url, ENT_QUOTES, "UTF-8"); ?>'>
                        <div class="card-image">
                            <img src="<?php echo htmlspecialchars($image_url, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($material['title'], ENT_QUOTES, 'UTF-8'); ?>" loading="lazy" onerror="this.onerror=null; this.src='<?php echo $fallback_image; ?>'">
                        </div>
                        <div class="card-header">
                            <i class="fas fa-<?php echo htmlspecialchars($icon, ENT_QUOTES, 'UTF-8'); ?>"></i>
                            <h3><?php echo htmlspecialchars($material['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                        </div>
                        <div class="card-body">
                            <span class="category-tag">
                                <i class="fas fa-tag"></i> <?php echo htmlspecialchars(ucfirst(str_replace('-', ' ', $category)), ENT_QUOTES, 'UTF-8'); ?>
                            </span>
                            <p><?php echo nl2br(htmlspecialchars(substr($material['content'], 0, 120), ENT_QUOTES, 'UTF-8')); ?>...</p>
                            <div class="read-more">
                                <i class="fas fa-mouse-pointer"></i> Click to read more
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 3rem;">
                    <i class="fas fa-book-open" style="font-size: 3rem; color: #8899cc;"></i>
                    <p>No review materials found for COC 1. Please add topics to the database.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="quiz-section">
            <h2><i class="fas fa-question-circle"></i> Test Your Knowledge</h2>
            <p>Ready to assess your understanding? Take the COC 1 quiz to see how well you've mastered all the topics.</p>
            <div class="quiz-buttons">
                <a href="quiz.php?coc=1" class="btn btn-primary"><i class="fas fa-play"></i> Take COC 1 Quiz</a>
                <a href="practice_mode.php?coc=1" class="btn btn-secondary"><i class="fas fa-chalkboard-teacher"></i> Practice Mode</a>
            </div>
        </div>
    </div>

    <div id="detailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Title</h2>
                <button class="close-modal" onclick="closeModal()">&times;</button>
            </div>
            <div class="image-container">
                <div class="zoom-controls">
                    <button class="zoom-btn" id="zoomOutBtn" title="Zoom Out (Mouse Wheel)">−</button>
                    <span class="zoom-level" id="zoomLevel">100%</span>
                    <button class="zoom-btn" id="zoomInBtn" title="Zoom In (Mouse Wheel)">+</button>
                    <button class="zoom-btn zoom-reset" id="zoomResetBtn" title="Reset Zoom">⟳</button>
                </div>
                <div class="modal-image" id="modalImageContainer">
                    <img id="modalImage" src="" alt="" referrerpolicy="no-referrer">
                </div>
                <div class="pan-hint">
                    <i class="fas fa-hand-paper"></i> Click and drag to pan
                </div>
            </div>
            <div class="modal-body">
                <span class="modal-category" id="modalCategory"></span>
                <p id="modalContent"></p>
            </div>
        </div>
    </div>

    <script>
        const FALLBACK_IMAGE = <?php echo json_encode($fallback_image); ?>;

        let currentModal = null;
        let currentZoom = 1;
        let currentImage = null;
        let isPanning = false;
        let startX = 0, startY = 0, scrollLeft = 0, scrollTop = 0;
        let savedScrollY = 0;

        function openModal(material, imageUrl) {
            const modal = document.getElementById('detailModal');
            const modalTitle = document.getElementById('modalTitle');
            const modalImage = document.getElementById('modalImage');
            const modalCategory = document.getElementById('modalCategory');
            const modalContent = document.getElementById('modalContent');
            const container = document.getElementById('modalImageContainer');

            if (!modal || !modalTitle || !modalImage || !modalCategory || !modalContent || !container) return;

            const category = String((material && material.category) || '').replace(/-/g, ' ');

            modalTitle.textContent = material && material.title ? material.title : '';
            modalCategory.textContent = category ? category.charAt(0).toUpperCase() + category.slice(1) : '';
            modalContent.textContent = material && material.content ? material.content : '';

            currentModal = modal;
            currentImage = modalImage;
            currentZoom = 1;
            isPanning = false;

            modalImage.style.transform = 'scale(1)';
            modalImage.alt = material && material.title ? material.title : 'Review material image';
            container.scrollLeft = 0;
            container.scrollTop = 0;
            container.style.cursor = 'default';

            const zoomLevel = document.getElementById('zoomLevel');
            if (zoomLevel) zoomLevel.textContent = '100%';

            savedScrollY = window.pageYOffset || document.documentElement.scrollTop || 0;
            modal.style.position = 'fixed';
            modal.style.top = '0';
            modal.style.right = '0';
            modal.style.bottom = '0';
            modal.style.left = '0';
            modal.style.width = '100%';
            modal.style.height = '100%';
            modal.style.minHeight = '0';
            modal.style.display = 'flex';
            modal.classList.add('active');

            document.documentElement.classList.add('modal-open');
            document.body.classList.add('modal-open');

            modalImage.onload = function () {
                container.scrollLeft = 0;
                container.scrollTop = 0;
            };

            modalImage.onerror = function () {
                modalImage.onerror = null;
                modalImage.src = FALLBACK_IMAGE;
            };

            modalImage.src = imageUrl || FALLBACK_IMAGE;
            setupPanning();
        }

        document.addEventListener('click', function (event) {
            const card = event.target.closest ? event.target.closest('.material-card') : null;
            if (!card || event.target.closest('button, a')) return;
            const data = card.getAttribute('data-material');
            const image = card.getAttribute('data-image');
            if (data) {
                try {
                    openModal(JSON.parse(data), image || '');
                } catch (e) {
                    console.error('Could not open material card:', e);
                }
            }
        }, false);

        (function setupZoomControlsOnce() {
            const container = document.getElementById('modalImageContainer');
            document.getElementById('zoomInBtn').addEventListener('click', () => zoomImage(0.1));
            document.getElementById('zoomOutBtn').addEventListener('click', () => zoomImage(-0.1));
            document.getElementById('zoomResetBtn').addEventListener('click', () => resetZoom());

            container.addEventListener('wheel', function (e) {
                if (!currentModal) return;
                e.preventDefault();
                zoomImage(e.deltaY < 0 ? 0.05 : -0.05);
            }, { passive: false });
        })();

        function setupPanning() {
            const container = document.getElementById('modalImageContainer');
            container.onmousedown = startPanning;
            container.onmouseleave = stopPanning;
            container.onmouseup = stopPanning;
            container.onmousemove = panImage;
        }

        function startPanning(e) {
            if (currentZoom <= 1) return;
            isPanning = true;
            const container = document.getElementById('modalImageContainer');
            startX = e.clientX;
            startY = e.clientY;
            scrollLeft = container.scrollLeft;
            scrollTop = container.scrollTop;
            container.style.cursor = 'grabbing';
        }

        function stopPanning() {
            isPanning = false;
            const container = document.getElementById('modalImageContainer');
            container.style.cursor = currentZoom > 1 ? 'grab' : 'default';
        }

        function panImage(e) {
            if (!isPanning) return;
            e.preventDefault();
            const container = document.getElementById('modalImageContainer');
            container.scrollLeft = scrollLeft - (e.clientX - startX) * 1.5;
            container.scrollTop = scrollTop - (e.clientY - startY) * 1.5;
        }

        function zoomImage(delta) {
            if (!currentImage) return;
            let newZoom = Math.min(Math.max(currentZoom + delta, 0.5), 3);
            if (newZoom === currentZoom) return;
            currentZoom = newZoom;
            currentImage.style.transform = `scale(${currentZoom})`;
            document.getElementById('zoomLevel').textContent = Math.round(currentZoom * 100) + '%';

            const container = document.getElementById('modalImageContainer');
            if (currentZoom > 1) {
                container.style.cursor = 'grab';
            } else {
                container.style.cursor = 'default';
                container.scrollLeft = 0;
                container.scrollTop = 0;
            }
        }

        function resetZoom() {
            currentZoom = 1;
            if (currentImage) currentImage.style.transform = 'scale(1)';
            document.getElementById('zoomLevel').textContent = '100%';
            const container = document.getElementById('modalImageContainer');
            if (container) {
                container.scrollLeft = 0;
                container.scrollTop = 0;
                container.style.cursor = 'default';
            }
        }

        function closeModal() {
            if (!currentModal) return;

            currentModal.classList.remove('active');
            currentModal.style.display = 'none';

            document.documentElement.classList.remove('modal-open');
            document.body.classList.remove('modal-open');

            currentModal = null;
            currentImage = null;
            resetZoom();

            window.scrollTo(0, savedScrollY);
        }

        window.addEventListener('click', function (event) {
            if (currentModal && event.target === currentModal) closeModal();
        }, false);

        window.addEventListener('touchend', function (event) {
            if (currentModal && event.target === currentModal) closeModal();
        }, { passive: true });

        document.addEventListener('keydown', function (event) {
            if (!currentModal) return;
            if (event.key === 'Escape') closeModal();
            if (event.key === '+' || event.key === '=') zoomImage(0.1);
            if (event.key === '-' || event.key === '_') zoomImage(-0.1);
            if (event.key === '0') resetZoom();
        });

        // ============================================
        // DARK / LIGHT MODE TOGGLE
        // ============================================
        (function () {
            const themeButton = document.getElementById('themeToggleNav');
            const themeIcon = document.getElementById('themeToggleIcon');
            const themeText = document.getElementById('themeToggleText');
            const storageKey = 'css_tesda_theme';

            function applyTheme(theme) {
                document.documentElement.setAttribute('data-theme', theme);
                try { localStorage.setItem(storageKey, theme); } catch (e) {}

                if (!themeButton) return;

                if (theme === 'light') {
                    themeIcon.className = 'fas fa-moon';
                    themeText.textContent = 'Dark Mode';
                    themeButton.title = 'Switch to dark mode';
                } else {
                    themeIcon.className = 'fas fa-sun';
                    themeText.textContent = 'Light Mode';
                    themeButton.title = 'Switch to light mode';
                }
            }

            let savedTheme = null;
            try { savedTheme = localStorage.getItem(storageKey); } catch (e) {}
            const initialTheme = savedTheme === 'light' ? 'light' : 'dark';

            if (themeButton) {
                applyTheme(initialTheme);
                themeButton.addEventListener('click', function (event) {
                    event.preventDefault();
                    const current = document.documentElement.getAttribute('data-theme') || 'dark';
                    applyTheme(current === 'dark' ? 'light' : 'dark');
                });
            } else {
                document.documentElement.setAttribute('data-theme', initialTheme);
            }
        })();

        // ============================================
        // BRIGHTNESS (overlay-based, WebView safe)
        // ============================================
        window.setPageBrightness = function (value) {
            var v = parseFloat(value);
            if (isNaN(v)) v = 100;
            if (v <= 3) v = v * 100;               // accept 0-1 style values
            v = Math.min(Math.max(v, 30), 100);    // clamp 30-100%
            var overlay = document.getElementById('brightnessOverlay');
            if (overlay) overlay.style.opacity = ((100 - v) / 100).toFixed(2);
            try { localStorage.setItem('css_tesda_brightness', String(Math.round(v))); } catch (e) {}
        };

        (function () {
            // Compatibility: if the old widget still applies filter: brightness(x) to html/body,
            // convert it to the overlay and strip the filter so the modal stays attached to the viewport.
            function convertFilter(el) {
                var f = el.style.filter || '';
                var m = f.match(/brightness\(\s*([\d.]+)\s*(%?)\s*\)/i);
                if (m) {
                    var n = parseFloat(m[1]);
                    window.setPageBrightness(m[2] === '%' ? n : n * 100);
                }
                if (f) el.style.removeProperty('filter');
            }

            var observer = new MutationObserver(function (mutations) {
                mutations.forEach(function (mu) {
                    if (mu.attributeName === 'style') convertFilter(mu.target);
                });
            });
            [document.documentElement, document.body].forEach(function (el) {
                convertFilter(el);
                observer.observe(el, { attributes: true, attributeFilter: ['style'] });
            });

            var saved = null;
            try { saved = localStorage.getItem('css_tesda_brightness'); } catch (e) {}
            window.setPageBrightness(saved !== null ? saved : <?php echo $initial_brightness; ?>);
        })();
    </script>
    <?php if (file_exists(__DIR__ . '/includes/brightness_widget.php')) { $global_brightness = $initial_brightness; include __DIR__ . '/includes/brightness_widget.php'; } ?>
</body>
</html>