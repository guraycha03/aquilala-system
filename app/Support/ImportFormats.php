<?php

namespace App\Support;

/**
 * Reads config/import-formats.php and derives everything the import screens
 * need from it: the validation allow-list, the file-input accept attribute,
 * and the human-readable label used in error messages.
 *
 * Nothing outside this class should hardcode an extension list — otherwise
 * widening the accepted formats becomes a scattered edit across controllers,
 * services and Blade views.
 */
final class ImportFormats
{
    /**
     * Extensions the bulk importers accept, lowercased and unique.
     *
     * @return array<int, string>
     */
    public static function extensions(): array
    {
        $extensions = config('import-formats.extensions', []);

        return array_values(array_unique(array_map(
            static fn ($extension) => strtolower(trim((string) $extension)),
            is_array($extensions) ? $extensions : []
        )));
    }

    /**
     * Extensions for which we ship a blank download template.
     *
     * @return array<int, string>
     */
    public static function templateExtensions(): array
    {
        $extensions = config('import-formats.template_extensions', []);

        return array_values(array_unique(array_map(
            static fn ($extension) => strtolower(trim((string) $extension)),
            is_array($extensions) ? $extensions : []
        )));
    }

    /**
     * Value for the <input type="file" accept="..."> attribute, e.g. ".csv,.xlsx".
     */
    public static function accept(): string
    {
        return implode(',', array_map(
            static fn (string $extension) => '.' . $extension,
            self::extensions()
        ));
    }

    /**
     * Human-readable list for messages, e.g. "CSV and XLSX".
     */
    public static function label(): string
    {
        return self::join(', ', ' and ');
    }

    /**
     * Same list joined for choices, e.g. "CSV or XLSX".
     */
    public static function orLabel(): string
    {
        return self::join(', ', ' or ');
    }

    /**
     * @return array<int, string>
     */
    private static function labels(): array
    {
        return array_map(
            static fn (string $extension) => strtoupper($extension),
            self::extensions()
        );
    }

    private static function join(string $separator, string $final): string
    {
        $labels = self::labels();

        if ($labels === []) {
            return '';
        }

        if (count($labels) === 1) {
            return $labels[0];
        }

        return implode($separator, array_slice($labels, 0, -1)) . $final . end($labels);
    }

    /**
     * Whether an uploaded filename's extension may be imported.
     */
    public static function allows(?string $extension): bool
    {
        if ($extension === null || $extension === '') {
            return false;
        }

        return in_array(strtolower($extension), self::extensions(), true);
    }

    /**
     * Whether we offer a blank template in this format.
     */
    public static function hasTemplate(?string $extension): bool
    {
        if ($extension === null || $extension === '') {
            return false;
        }

        return in_array(strtolower($extension), self::templateExtensions(), true);
    }
}
