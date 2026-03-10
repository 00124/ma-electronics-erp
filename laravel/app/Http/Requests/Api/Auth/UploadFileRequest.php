<?php

namespace App\Http\Requests\Api\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

class UploadFileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $folder = $this->folder;

        $rules = [
            'folder' => 'required'
        ];

        if ($this->has('image')) {
            // Strict validation: only allow specific image extensions and MIME types
            $rules['image'] = 'required|image|mimes:jpg,jpeg,png,gif,webp|max:20000';
        }

        if ($this->has('file')) {
            // Strict validation based on folder type
            if ($folder == 'expenses') {
                $rules['file'] = 'required|mimes:csv,txt,xlsx,xls,pdf,docx,jpg,jpeg,png|max:20000';
            } else {
                $rules['file'] = 'required|mimes:csv,txt,xlsx,xls,pdf,docx,jpg,jpeg,png|max:20000';
            }
        }

        return $rules;
    }

    /**
     * Custom validation messages
     */
    public function messages()
    {
        return [
            'image.image' => 'The uploaded file must be a valid image.',
            'image.mimes' => 'Only JPG, JPEG, PNG, GIF, and WEBP images are allowed.',
            'file.mimes' => 'Invalid file type. Only CSV, TXT, XLSX, XLS, PDF, DOCX, JPG, JPEG, and PNG files are allowed.',
        ];
    }

    /**
     * Additional validation after standard rules pass
     * This provides bulletproof protection against malicious files
     */
    protected function passedValidation()
    {
        $uploadedFile = $this->hasFile('image') ? $this->file('image') : $this->file('file');

        if ($uploadedFile) {
            $originalName = $uploadedFile->getClientOriginalName();
            $extension = strtolower($uploadedFile->getClientOriginalExtension());

            // 1. Check for dangerous extensions in the filename (including double extensions)
            if (preg_match('/\.(php[0-9]?|phtml|phar|phps|asp|aspx|jsp|sh|py|pl|cgi|exe|bat|cmd|com|htaccess|htpasswd)($|\.)/i', $originalName)) {
                throw ValidationException::withMessages([
                    $this->hasFile('image') ? 'image' : 'file' => ['The file contains a potentially dangerous extension and cannot be uploaded.']
                ]);
            }

            // 2. Validate against strict whitelist
            $allowedExtensions = $this->getAllowedExtensions($this->folder, $this->hasFile('image'));

            if (!in_array($extension, $allowedExtensions)) {
                throw ValidationException::withMessages([
                    $this->hasFile('image') ? 'image' : 'file' => [
                        'The file extension "' . $extension . '" is not allowed. Allowed extensions: ' . implode(', ', $allowedExtensions)
                    ]
                ]);
            }

            // 3. Additional check: ensure no suspicious characters in filename
            if (preg_match('/[<>:"|?*\x00-\x1F]/', $originalName)) {
                throw ValidationException::withMessages([
                    $this->hasFile('image') ? 'image' : 'file' => ['The filename contains invalid characters.']
                ]);
            }
        }
    }

    /**
     * Get allowed file extensions based on folder and file type
     */
    private function getAllowedExtensions($folder, $isImage = false)
    {
        if ($isImage) {
            // For image uploads, only allow safe image formats
            return ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        }

        // For file uploads, define allowed extensions per folder
        $allowedByFolder = [
            'expenses' => ['csv', 'txt', 'xlsx', 'xls', 'pdf', 'docx', 'jpg', 'jpeg', 'png'],
            'offline-requests' => ['csv', 'txt', 'xlsx', 'xls', 'pdf', 'docx', 'jpg', 'jpeg', 'png'],
        ];

        // Default allowed extensions for file uploads
        $defaultAllowed = ['csv', 'txt', 'xlsx', 'xls', 'pdf', 'docx', 'jpg', 'jpeg', 'png'];

        return $allowedByFolder[$folder] ?? $defaultAllowed;
    }
}
