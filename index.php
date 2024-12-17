<?php

include 'lib/parsedown/Parsedown.php';

$config = parse_ini_file('config.ini');

$DIRECTORY = $config['DIRECTORY'] ?? '.';
$SEPARATOR = $config['SEPARATOR'] ?? DIRECTORY_SEPARATOR;
$RELATIVE_PATH = $config['RELATIVE_PATH'] ?? '';

$request_directory = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (str_ends_with($request_directory, 'index.php')) {
    $request_directory = substr($request_directory, 0, -9);
}
if (str_ends_with($request_directory, '/')) {
    $request_directory = substr($request_directory, 0, -1);
}
if (str_starts_with($request_directory, '/')) {
    $request_directory = substr($request_directory, 1);
}
if ($request_directory) {
    $directory = $DIRECTORY . $SEPARATOR . $request_directory;
} else {
    $directory = $DIRECTORY;
}

$lang_file = json_decode(file_get_contents('./languages.json'));  // Language file

// 引数から指定された言語を取得 (https://example.com/index.php?lang=ja)
$lang = $_GET['lang'] ?? 'ja';

// $directoryに対してパンくずリストを生成
$breadcrumbs = [['name' => 'Home', 'url' => '/']];
$directories = explode('/', $request_directory);
$url = '';
foreach ($directories as $_directory) {
    if (!$_directory) {
        continue;
    }
    $url .= '/' . $_directory;
    $breadcrumbs[] = [
        'name' => $_directory,
        'url' => $url,
    ];
}

$lang_dict = $lang_file->{$lang};

echo '<!DOCTYPE html>';
echo '
<html lang="'. $lang.'">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="/' . $RELATIVE_PATH . '/styles.css'.'">
    <title>'.$lang_dict->{'title'}.'</title>
</head>
<body>
<header>
    <h1>'.$lang_dict->{'title'}.'</h1>
    <nav>
        <ul>';
if ($lang === 'ja') {
    echo '<li><a href="?lang=en">English</a></li>';
} else {
    echo '<li><a href="?lang=ja">日本語</a></li>';
}
echo '</ul>
    </nav>
</header>
<main>
    <div class="breadcrumb">
        <nav>
            <ul>';
foreach ($breadcrumbs as $breadcrumb) {
    echo '<li><a href="'.$breadcrumb['url'].'">'.$breadcrumb['name'].'</a></li>';
    if ($breadcrumb !== end($breadcrumbs)) {
        echo '<li>></li>';
    }
}
echo '
            </ul>
        </nav>
    </div>
    <div class="content">
        <h1>' . end($breadcrumbs)['name'] . '</h1>
    </div>
    <div class="files">
        <table>
            <tr>
                <th style="width: 32px;"></th>
                <th>'.$lang_dict->{'file'}.'</th>
                <th>'.$lang_dict->{'size'}.'</th>
                <th>'.$lang_dict->{'mtime'}.'</th>
            </tr>';

// ディレクトリ内のファイル ディレクトリを取得
$files = [];
foreach (scandir($directory) as $file) {
    if ($file === '.' || $file === '..') {
        continue;
    }
    if (is_file($directory . $SEPARATOR . $file)) {
        $files[] = [
            'name' => $file,
            'mtime' => filemtime($directory . $SEPARATOR . $file),
            'size' => filesize($directory . $SEPARATOR . $file),
            'type' => 'file'
        ];
    } else {
        $files[] = [
            'name' => $file,
            'mtime' => filemtime($directory . $SEPARATOR . $file),
            'size' => 0,
            'type' => 'directory'
        ];
    }
}

// ファイルを更新日時の降順にソート
usort($files, function ($a, $b) {
    return $b['mtime'] - $a['mtime'];
});

// ディレクトリをファイルの前に表示
foreach ($files as $file) {
    if ($file['type'] === 'file') {
        continue;
    }
    echo '<tr>';
    echo '<td><svg width="28px" height="28px" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M3 8.2C3 7.07989 3 6.51984 3.21799 6.09202C3.40973 5.71569 3.71569 5.40973 4.09202 5.21799C4.51984 5 5.0799 5 6.2 5H9.67452C10.1637 5 10.4083 5 10.6385 5.05526C10.8425 5.10425 11.0376 5.18506 11.2166 5.29472C11.4184 5.4184 11.5914 5.59135 11.9373 5.93726L12.0627 6.06274C12.4086 6.40865 12.5816 6.5816 12.7834 6.70528C12.9624 6.81494 13.1575 6.89575 13.3615 6.94474C13.5917 7 13.8363 7 14.3255 7H17.8C18.9201 7 19.4802 7 19.908 7.21799C20.2843 7.40973 20.5903 7.71569 20.782 8.09202C21 8.51984 21 9.0799 21 10.2V15.8C21 16.9201 21 17.4802 20.782 17.908C20.5903 18.2843 20.2843 18.5903 19.908 18.782C19.4802 19 18.9201 19 17.8 19H6.2C5.07989 19 4.51984 19 4.09202 18.782C3.71569 18.5903 3.40973 18.2843 3.21799 17.908C3 17.4802 3 16.9201 3 15.8V8.2Z" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg></td>';
    if ($request_directory) {
        echo '<td><a href="'.'/'.$request_directory.'/'.$file['name'].'">'.$file['name'].'</a></td>';
    } else {
        echo '<td><a href="'.$file['name'].'">'.$file['name'].'</a></td>';
    }
    echo '<td></td>';
    echo '<td>'.date('Y-m-d H:i:s', $file['mtime']).'</td>';
    echo '</tr>';
}

foreach ($files as $file) {
    if ($file['type'] === 'directory') {
        continue;
    }
    echo '<tr>';
    echo '<td><svg fill="#000000" height="28px" width="28px" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" 
	 viewBox="0 0 58 58" xml:space="preserve">
<g>
	<path d="M6.5,41v15c0,1.009,1.22,2,2.463,2h40.074c1.243,0,2.463-0.991,2.463-2V41H6.5z M19.5,52c-2.206,0-4-1.794-4-4s1.794-4,4-4
		s4,1.794,4,4S21.706,52,19.5,52z M29.5,52c-2.206,0-4-1.794-4-4s1.794-4,4-4s4,1.794,4,4S31.706,52,29.5,52z M39.5,52
		c-2.206,0-4-1.794-4-4s1.794-4,4-4s4,1.794,4,4S41.706,52,39.5,52z"/>
	<path d="M51.5,39V13.978c0-0.766-0.092-1.333-0.55-1.792L39.313,0.55C38.964,0.201,38.48,0,37.985,0H8.963
		C7.777,0,6.5,0.916,6.5,2.926V39H51.5z M37.5,3.391c0-0.458,0.553-0.687,0.877-0.363l10.095,10.095
		C48.796,13.447,48.567,14,48.109,14H37.5V3.391z"/>
</g>
</svg></td>';
    if ($request_directory) {
        echo '<td><a href="'.'/'.$request_directory.'/'.$file['name'].'">'.$file['name'].'</a></td>';
    } else {
        echo '<td><a href="'.$file['name'].'">'.$file['name'].'</a></td>';
    }
    if ($file['size'] >= 1024 * 1024 * 1024) {
        echo '<td>'.round($file['size'] / 1024 / 1024 / 1024, 2).' GB</td>';
    } else if ($file['size'] >= 1024 * 1024) {
        echo '<td>'.round($file['size'] / 1024 / 1024, 2).' MB</td>';
    } else if ($file['size'] >= 1024) {
        echo '<td>'.round($file['size'] / 1024, 2).' KB</td>';
    } else {
        echo '<td>'.$file['size'].' B</td>';
    }
    echo '<td>'.date('Y-m-d H:i:s', $file['mtime']).'</td>';
    echo '</tr>';
}
echo '</table>';


// 表示できそうなファイルがあったら下の方に出しておく
$SUPPORTED_FILES = [
    'index.html',
    'index.php',
    'README.md',
    'LICENSE',
    'CHANGELOG.md',
    '*.png',
    '*.jpg',
    '*.jpeg',
    '*.webp',
    '*.gif',
    '*.bmp',
    '*.svg',
    '*.mp4',
    '*.webm',
    '*.json',
];

$displaying_files = [];
foreach ($files as $file) {
    foreach ($SUPPORTED_FILES as $supported_file) {
        if (fnmatch($supported_file, $file['name'])) {
            if ($file['size'] >= 4 * 1024 * 1024) {
                $displaying_files[] = [
                    'name' => $file['name'],
                    'code' => '<p>'.$lang_dict->{'file_too_large'}.'</p>',
                ];
                continue;
            }
            switch ($supported_file) {
                case 'index.html':
                case 'index.php':
                    $code = '<iframe style="width:100%;height:300px;" src="'.'/'.$request_directory.$SEPARATOR.$file['name'].'"></iframe>';
                    break;
                case 'README.md':
                case 'CHANGELOG.md':
                    $code = (new Parsedown())->text(file_get_contents($directory.$SEPARATOR.$file['name']));
                    break;
                case '*.png':
                case '*.jpg':
                case '*.jpeg':
                case '*.webp':
                case '*.gif':
                case '*.bmp':
                case '*.svg':
                    $code = '<img src="'.'/'.$request_directory.$SEPARATOR.$file['name'].'">';
                    break;
                case '*.mp4':
                case '*.webm':
                    $code = '<video controls><source src="'.'/'.$request_directory.$SEPARATOR.$file['name'].'"></video>';
                    break;
                case '*.json':
                    $code = '<pre>'.file_get_contents($directory.$SEPARATOR.$file['name']).'</pre>';
                    break;
                default:
                    $code = '<pre>'.file_get_contents($directory.$SEPARATOR.$file['name']).'</pre>';
            }
            $displaying_files[] = [
                'name' => $file['name'],
                'code' => $code ?? '',
            ];
            if (count($displaying_files) >= 5) {
                break 2;
            }
        }
    }
}

foreach ($displaying_files as $displaying_file) {
    echo '<div class="displaying-file">';
    echo '<h2 class="title">'.$displaying_file['name'].'</h2>';
    echo $displaying_file['code'];
    echo '</div>';
}


echo '
    </div>
    <footer>
        <p>&copy; 2024 OkayuGroup</p>
    </footer>
</main>
</body>
</html>';
