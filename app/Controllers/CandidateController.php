<?php

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\Candidate;
use App\Models\Position;
use App\Models\AuditLog;
use App\Helpers\Security;
use App\Helpers\Response;

class CandidateController extends Controller
{
    public function index(): void
    {
        AuthMiddleware::handle();

        $candidates = Candidate::getAllWithPosition();
        $positions = Position::getActiveOrdered();

        $this->render('admin/candidates/index', [
            'candidates' => $candidates,
            'positions' => $positions,
            'csrf_token' => Security::generateCSRFToken()
        ]);
    }

    public function create(): void
    {
        AuthMiddleware::handle();

        $positions = Position::getActiveOrdered();
        $this->render('admin/candidates/create', [
            'positions' => $positions,
            'csrf_token' => Security::generateCSRFToken()
        ]);
    }

    public function store(): void
    {
        AuthMiddleware::handle();

        $positionId = (int)($_POST['position_id'] ?? 0);
        $fullname = Security::sanitize($_POST['fullname'] ?? '');
        $manifesto = Security::sanitize($_POST['manifesto'] ?? '');
        $profile = Security::sanitize($_POST['profile'] ?? '');
        $displayOrder = (int)($_POST['display_order'] ?? 1);
        $active = isset($_POST['active']) ? 1 : 0;

        if (empty($fullname) || empty($manifesto) || $positionId <= 0) {
            Response::json(['success' => false, 'message' => 'Full Name, Position, and Manifesto are required.'], 422);
        }

        // Image Upload Handling (Max 5MB, Image only)
        $uniPhotoPath = $this->handleUpload('university_photo');
        $recentPhotoPath = $this->handleUpload('recent_photo');

        $candidateId = Candidate::create([
            'position_id' => $positionId,
            'fullname' => $fullname,
            'manifesto' => $manifesto,
            'profile' => $profile,
            'university_photo' => $uniPhotoPath,
            'recent_photo' => $recentPhotoPath,
            'display_order' => $displayOrder,
            'active' => $active,
        ]);

        AuditLog::log("Created candidate: {$fullname} (ID: {$candidateId})", "Candidate Management");

        Response::json([
            'success' => true,
            'message' => 'Candidate created successfully.',
            'redirect' => '/admin/candidates'
        ]);
    }

    public function edit(int $id): void
    {
        AuthMiddleware::handle();

        $candidate = Candidate::find($id);
        if (!$candidate) {
            header('Location: /admin/candidates');
            exit;
        }

        $positions = Position::getActiveOrdered();
        $this->render('admin/candidates/edit', [
            'candidate' => $candidate,
            'positions' => $positions,
            'csrf_token' => Security::generateCSRFToken()
        ]);
    }

    public function update(int $id): void
    {
        AuthMiddleware::handle();

        $candidate = Candidate::find($id);
        if (!$candidate) {
            Response::json(['success' => false, 'message' => 'Candidate not found.'], 404);
        }

        $positionId = (int)($_POST['position_id'] ?? 0);
        $fullname = Security::sanitize($_POST['fullname'] ?? '');
        $manifesto = Security::sanitize($_POST['manifesto'] ?? '');
        $profile = Security::sanitize($_POST['profile'] ?? '');
        $displayOrder = (int)($_POST['display_order'] ?? 1);
        $active = isset($_POST['active']) ? 1 : 0;

        $uniPhotoPath = $this->handleUpload('university_photo');
        $recentPhotoPath = $this->handleUpload('recent_photo');

        Candidate::updateCandidate($id, [
            'position_id' => $positionId,
            'fullname' => $fullname,
            'manifesto' => $manifesto,
            'profile' => $profile,
            'university_photo' => $uniPhotoPath,
            'recent_photo' => $recentPhotoPath,
            'display_order' => $displayOrder,
            'active' => $active,
        ]);

        AuditLog::log("Updated candidate: {$fullname} (ID: {$id})", "Candidate Management");

        Response::json([
            'success' => true,
            'message' => 'Candidate updated successfully.',
            'redirect' => '/admin/candidates'
        ]);
    }

    public function toggleStatus(int $id): void
    {
        AuthMiddleware::handle();

        $candidate = Candidate::find($id);
        if ($candidate) {
            $newActive = $candidate['active'] ? 0 : 1;
            Candidate::updateCandidate($id, [
                'position_id' => $candidate['position_id'],
                'fullname' => $candidate['fullname'],
                'manifesto' => $candidate['manifesto'],
                'profile' => $candidate['profile'],
                'display_order' => $candidate['display_order'],
                'active' => $newActive
            ]);

            $statusText = $newActive ? 'enabled' : 'disabled';
            AuditLog::log("Toggled status for candidate ID {$id} to {$statusText}", "Candidate Management");

            Response::json(['success' => true, 'message' => "Candidate {$statusText} successfully."]);
        }

        Response::json(['success' => false, 'message' => 'Candidate not found.'], 404);
    }

    public function delete(int $id): void
    {
        AuthMiddleware::handle();

        $candidate = Candidate::find($id);
        if ($candidate) {
            Candidate::delete($id);
            AuditLog::log("Deleted candidate: {$candidate['fullname']} (ID: {$id})", "Candidate Management");
            Response::json(['success' => true, 'message' => 'Candidate deleted successfully.']);
        }

        Response::json(['success' => false, 'message' => 'Candidate not found.'], 404);
    }

    private function handleUpload(string $field): ?string
    {
        if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $file = $_FILES[$field];
        
        // 5MB Limit
        if ($file['size'] > 5 * 1024 * 1024) {
            Response::json(['success' => false, 'message' => 'File size exceeds maximum allowed size of 5MB.'], 422);
        }

        // Image validation
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowedMimes)) {
            Response::json(['success' => false, 'message' => 'Only JPG, PNG, and WebP images are allowed.'], 422);
        }

        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('cand_') . '_' . time() . '.' . $extension;
        $uploadDir = __DIR__ . '/../../public/uploads/candidates/';

        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0777, true);
        }

        $destination = $uploadDir . $filename;
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return '/uploads/candidates/' . $filename;
        }

        return null;
    }
}
