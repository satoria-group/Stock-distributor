<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ImapService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EmailAttachmentController extends Controller
{
    public function __construct(protected ImapService $imapService)
    {
    }

    /**
     * Securely download email attachment on-demand.
     */
    public function download(Request $request, string $uid, string $attachmentId)
    {
        $user = $request->user();
        if (! $user) {
            abort(Response::HTTP_UNAUTHORIZED);
        }

        // Authorization check: Admin, Logistik, or user with stock.upload/stock.view permission
        $isAuthorized = $user->hasRole(User::ROLE_ADMIN)
            || $user->hasRole(User::ROLE_LOGISTIK)
            || $user->can('stock.upload')
            || $user->can('stock.view');

        if (! $isAuthorized) {
            abort(Response::HTTP_FORBIDDEN, 'Anda tidak memiliki izin untuk mengunduh lampiran email.');
        }

        $attachment = $this->imapService->getAttachment($uid, $attachmentId);

        if (! $attachment) {
            abort(Response::HTTP_NOT_FOUND, 'Lampiran tidak ditemukan atau mail server tidak merespons.');
        }

        // Strict filename sanitization:
        // Remove directory traversal, slashes, null bytes, and non-printable chars
        $rawFilename = $attachment['filename'];
        $cleanFilename = basename(str_replace(['\\', '/', "\0", '..'], '', $rawFilename));
        $cleanFilename = preg_replace('/[\x00-\x1F\x7F]/u', '', $cleanFilename);

        if (trim($cleanFilename) === '' || $cleanFilename === '.') {
            $cleanFilename = "attachment_{$attachmentId}.bin";
        }

        $mimeType = $attachment['mime_type'] ?: 'application/octet-stream';

        // Disallow dangerous executables from executing inline
        $dangerousMimes = [
            'text/html',
            'application/javascript',
            'text/javascript',
            'application/x-msdownload',
            'application/x-sh',
            'application/x-php',
            'application/xhtml+xml',
        ];

        if (in_array(strtolower($mimeType), $dangerousMimes, true)) {
            $mimeType = 'application/octet-stream';
        }

        return response($attachment['content'])
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', 'attachment; filename="'.$cleanFilename.'"')
            ->header('X-Content-Type-Options', 'nosniff')
            ->header('Cache-Control', 'private, no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache');
    }
}
