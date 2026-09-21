<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Raised when the local Tesseract OCR engine cannot be found or fails to
 * extract text from an uploaded document.
 */
class OcrException extends RuntimeException
{
}