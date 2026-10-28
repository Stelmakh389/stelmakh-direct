<?php
function legal_inline_markdown(string $text): string
{
    $text = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    $text = preg_replace('/\*\*(.+?)\*\*/u', '<strong>$1</strong>', $text);
    $text = preg_replace(
        '/\[(.+?)\]\((https?:\/\/[^)]+)\)/u',
        '<a href="$2" target="_blank" rel="noopener noreferrer">$1</a>',
        $text
    );
    $text = preg_replace_callback('/(?<!href=")(https?:\/\/[^\s<]+)/u', static function (array $matches): string {
        $url = rtrim($matches[1], '.,;:');
        $tail = substr($matches[1], strlen($url));

        return '<a href="' . $url . '" target="_blank" rel="noopener noreferrer">' . $url . '</a>' . $tail;
    }, $text);

    return $text;
}

function render_legal_document(string $file): void
{
    $path = __DIR__ . '/../content/legal/' . $file;

    if (!is_file($path)) {
        echo '<p>Документ временно недоступен.</p>';
        return;
    }

    $lines = preg_split('/\R/u', trim((string) file_get_contents($path)));
    $inList = false;

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '') {
            if ($inList) {
                echo "</ul>\n";
                $inList = false;
            }
            continue;
        }

        if (preg_match('/^# (.+)$/u', $line, $matches)) {
            if ($inList) {
                echo "</ul>\n";
                $inList = false;
            }
            echo '<h1>' . legal_inline_markdown($matches[1]) . "</h1>\n";
            continue;
        }

        if (preg_match('/^## (.+)$/u', $line, $matches)) {
            if ($inList) {
                echo "</ul>\n";
                $inList = false;
            }
            echo '<h2>' . legal_inline_markdown($matches[1]) . "</h2>\n";
            continue;
        }

        if (preg_match('/^- (.+)$/u', $line, $matches)) {
            if (!$inList) {
                echo "<ul>\n";
                $inList = true;
            }
            echo '<li>' . legal_inline_markdown($matches[1]) . "</li>\n";
            continue;
        }

        if ($inList) {
            echo "</ul>\n";
            $inList = false;
        }

        echo '<p>' . legal_inline_markdown($line) . "</p>\n";
    }

    if ($inList) {
        echo "</ul>\n";
    }
}
?>
