<?php

include 'lib/parsedown/Parsedown.php';

$config = parse_ini_file('config.ini');

$DIRECTORY = $config['DIRECTORY'] ?? '.';
$SEPARATOR = $config['SEPARATOR'] ?? DIRECTORY_SEPARATOR;
$RELATIVE_PATH = $config['RELATIVE_PATH'] ?? '';

$request_directory = $_GET['dir'] ?? '';
if (str_ends_with($request_directory, '/')) {
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
    echo '<td><a href="'.'/'.$request_directory.'/'.$file['name'].'">'.$file['name'].'</a></td>';
    echo '<td></td>';
    echo '<td>'.date('Y-m-d H:i:s', $file['mtime']).'</td>';
    echo '</tr>';
}

foreach ($files as $file) {
    if ($file['type'] === 'directory') {
        continue;
    }
    echo '<tr>';
    echo '<td><a href="'.'/'.$request_directory.'/'.$file['name'].'">'.$file['name'].'</a></td>';
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
                    $code = '<iframe src="'.$directory.$SEPARATOR.$file['name'].'"></iframe>';
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
                    echo '<img src="'.$directory.$SEPARATOR.$file['name'].'">';
                    break;
                case '*.mp4':
                case '*.webm':
                    $code = '<video controls><source src="'.$directory.$SEPARATOR.$file['name'].'"></video>';
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
