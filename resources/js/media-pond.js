import * as FilePond from 'filepond';
import FilePondPluginImagePreview from 'filepond-plugin-image-preview';
import FilePondPluginFileValidateType from 'filepond-plugin-file-validate-type';
import FilePondPluginFileValidateSize from 'filepond-plugin-file-validate-size';
import FilePondPluginImageResize from 'filepond-plugin-image-resize';
import FilePondPluginImageTransform from 'filepond-plugin-image-transform';
import FilePondPluginImageCrop from 'filepond-plugin-image-crop';

import 'filepond/dist/filepond.min.css';
import 'filepond-plugin-image-preview/dist/filepond-plugin-image-preview.min.css';

import heic2any from 'heic2any';

FilePond.registerPlugin(
    FilePondPluginImagePreview,
    FilePondPluginFileValidateType,
    FilePondPluginFileValidateSize,
    FilePondPluginImageResize,
    FilePondPluginImageTransform,
    FilePondPluginImageCrop,
);

window.FilePond = FilePond;

const CHUNK_SIZE = 5 * 1024 * 1024;

const ACCEPTED_TYPES = {
    video: ['video/mp4', 'video/quicktime', 'video/x-msvideo'],
    audio: ['audio/mpeg', 'audio/wav', 'audio/aac', 'audio/x-m4a', 'audio/mp4'],
    pdf:   ['application/pdf'],
    image: ['image/jpeg', 'image/png', 'image/webp', 'image/heic'],
};

const MAX_SIZES = {
    video: '500MB',
    audio: '100MB',
    pdf:   '50MB',
    image: '10MB',
};

const MAX_VIDEO_DURATION = {
    default:    5  * 60,
    subscribed: 15 * 60,
};

/**
 * SHARED ENGINE — chunked upload
 */
function chunkedProcess({ format, purpose, modelType, modelId, csrf, onComplete }) {
    return (fieldName, file, metadata, load, error, progress, abort) => {
        const uploadId    = crypto.randomUUID();
        const totalChunks = Math.ceil(file.size / CHUNK_SIZE);
        let cancelled     = false;

        (async () => {
            try {
                for (let i = 0; i < totalChunks; i++) {
                    if (cancelled) return;

                    const chunk = file.slice(
                        i * CHUNK_SIZE,
                        Math.min((i + 1) * CHUNK_SIZE, file.size)
                    );

                    const fd = new FormData();
                    fd.append('upload_id', uploadId);
                    fd.append('chunk_index', i);
                    fd.append('file', chunk);

                    const res = await fetch('/upload/chunk', {
                        method:  'POST',
                        headers: { 'X-CSRF-TOKEN': csrf },
                        body:    fd,
                    });

                    if (!res.ok) throw new Error(`Chunk ${i} failed`);
                    progress(true, i + 1, totalChunks);
                }

                const completeRes = await fetch('/upload/complete', {
                    method:  'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({
                        upload_id:    uploadId,
                        total_chunks: totalChunks,
                        filename:     file.name,
                        type:         format,
                        purpose:      purpose,
                        model_type:   modelType ?? null,
                        model_id:     modelId ?? null,
                    }),
                });

                if (!completeRes.ok) {
                    const err = await completeRes.json();
                    throw new Error(err.error ?? 'Assembly failed');
                }

                const finalPath = await completeRes.text();
                if (onComplete) onComplete(finalPath);
                load(finalPath);

            } catch (err) {
                error(err.message);
            }
        })();

        return {
            abort: () => {
                cancelled = true;
                abort();
            },
        };
    };
}

/**
 * SHARED — HEIC conversion
 */
function heicConversionHook(format) {
    return (fileItem) => {
        const file   = fileItem.file;
        const isHeic = file.name.toLowerCase().endsWith('.heic') || file.type === 'image/heic';

        if (isHeic && format === 'image') {
            return heic2any({ blob: file, toType: 'image/jpeg', quality: 0.8 })
                .then((converted) => {
                    fileItem.file = new File(
                        [converted],
                        file.name.replace(/\.[^/.]+$/, '.jpg'),
                        { type: 'image/jpeg' }
                    );
                    return true;
                })
                .catch(() => true);
        }

        return true;
    };
}

/**
 * SHARED — Video duration check
 */
function checkVideoDuration(file, maxSeconds) {
    return new Promise((resolve, reject) => {
        const video     = document.createElement('video');
        video.preload   = 'metadata';
        video.onloadedmetadata = () => {
            URL.revokeObjectURL(video.src);
            if (video.duration > maxSeconds) {
                reject(new Error(`Video exceeds maximum duration of ${Math.floor(maxSeconds / 60)} minutes.`));
            } else {
                resolve(true);
            }
        };
        video.onerror = () => reject(new Error('Could not read video duration.'));
        video.src     = URL.createObjectURL(file);
    });
}

/**
 * GROUP 1 — Content media
 */
window.initMediaPond = function (el, config = {}) {
    const {
        wire         = null,
        modelId      = null,
        modelType    = 'editorial',
        format       = 'video',
        multiple     = false,
        isSubscribed = false,
    } = config;

    const csrf       = document.querySelector('meta[name="csrf-token"]').content;
    const maxSeconds = isSubscribed
        ? MAX_VIDEO_DURATION.subscribed
        : MAX_VIDEO_DURATION.default;

    return FilePond.create(el, {
        allowMultiple:     multiple,
        maxFileSize:       MAX_SIZES[format] ?? '200MB',
        acceptedFileTypes: ACCEPTED_TYPES[format] ?? [],
        labelIdle:         `Drag & drop your ${format} or <span class="filepond--label-action">browse</span>`,

        beforeAddFile: format === 'video'
            ? (fileItem) => checkVideoDuration(fileItem.file, maxSeconds)
                .then(() => true)
                .catch((err) => { alert(err.message); return false; })
            : heicConversionHook(format),

        server: {
            process: chunkedProcess({
                format,
                purpose:   'content',
                modelType,
                modelId,
                csrf,
                onComplete: (finalPath) => {
                    if (wire) wire.notifyUploadComplete(finalPath, format);
                },
            }),
            revert: {
                url:     '/upload/revert',
                method:  'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf },
            },
        },
    });
};

/**
 * GROUP 2 — Cover images
 */
window.initCoverPond = function (el, config = {}) {
    const {
        wire      = null,
        modelId   = null,
        modelType = 'editorial',
    } = config;

    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    return FilePond.create(el, {
        allowMultiple:                false,
        maxFileSize:                  MAX_SIZES.image,
        acceptedFileTypes:            ACCEPTED_TYPES.image,
        stylePanelLayout:             'compact',
        imagePreviewHeight:           160,
        imageCropAspectRatio:         '1200:630',
        imageResizeTargetWidth:       1200,
        imageResizeTargetHeight:      630,
        imageResizeMode:              'cover',
        imageTransformOutputMimeType: 'image/jpeg',
        imageTransformOutputQuality:  85,
        labelIdle:                    'Drag & drop cover or <span class="filepond--label-action">browse</span>',
        beforeAddFile:                heicConversionHook('image'),

        server: {
            process: chunkedProcess({
                format:    'image',
                purpose:   'cover',
                modelType,
                modelId,
                csrf,
                onComplete: (finalPath) => {
                    if (wire) wire.notifyUploadComplete(finalPath, 'image');
                },
            }),
            revert: {
                url:     '/upload/revert',
                method:  'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf },
            },
        },
    });
};

/**
 * GROUP 3 — Profile picture
 */
window.initProfilePond = function (el, config = {}) {
    const { wire = null } = config;

    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    return FilePond.create(el, {
        allowMultiple:                false,
        maxFileSize:                  MAX_SIZES.image,
        acceptedFileTypes:            ACCEPTED_TYPES.image,
        stylePanelLayout:             'compact circle',
        imagePreviewHeight:           100,
        imageCropAspectRatio:         '1:1',
        imageResizeTargetWidth:       400,
        imageResizeTargetHeight:      400,
        imageResizeMode:              'cover',
        imageTransformOutputMimeType: 'image/jpeg',
        imageTransformOutputQuality:  85,
        labelIdle:                    'Drag & drop photo or <span class="filepond--label-action">browse</span>',
        beforeAddFile:                heicConversionHook('image'),

        server: {
            process: chunkedProcess({
                format:    'image',
                purpose:   'profile',
                modelType: 'user',
                modelId:   null,
                csrf,
                onComplete: (finalPath) => {
                    if (wire) wire.notifyUploadComplete(finalPath, 'image');
                },
            }),
            revert: {
                url:     '/upload/revert',
                method:  'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf },
            },
        },
    });
};






/**
 * GROUP 4 — chatroom attachments
 * Dispatches browser event — Alpine catches it and calls $wire.addAttachment()
 */


window.initChatRoomMediaPond = function (el, config = {}) {
    const { onComplete = null } = config;
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    let currentModelId = null;

    const pond = FilePond.create(el, {
        allowMultiple:     true,
        maxFiles:          5,
        maxFileSize:       '500MB',
        acceptedFileTypes: [
            ...ACCEPTED_TYPES.image,
            ...ACCEPTED_TYPES.video,
            ...ACCEPTED_TYPES.audio,
            ...ACCEPTED_TYPES.pdf,
        ],
        stylePanelLayout: 'compact',
        labelIdle: '',

        server: {
            process: (fieldName, file, metadata, load, error, progress, abort) => {
                let format = 'image';
                if (file.type.startsWith('video/'))       format = 'video';
                else if (file.type.startsWith('audio/'))  format = 'audio';
                else if (file.type === 'application/pdf') format = 'pdf';

                const processor = chunkedProcess({
                    format,
                    purpose:   'message',
                    modelType: 'chat_room_message',
                    modelId:   currentModelId,
                    csrf,
                    onComplete: (finalPath) => {
                        if (onComplete) onComplete(finalPath);
                    },
                });

                return processor(fieldName, file, metadata, load, error, progress, abort);
            },
            revert: {
                url:     '/upload/revert',
                method:  'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf },
            },
        },
    });

    pond.setModelId = (id) => {
        currentModelId = id;
    };

    return pond;
};
/**
 * GROUP 5 — Message attachments
 * Dispatches browser event — Alpine catches it and calls $wire.addAttachment()
 */

window.initMessageMediaPond = function (el, config = {}) {
    const { onComplete = null } = config;
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    let currentModelId   = null;
    let currentModelType = 'message';

    const pond = FilePond.create(el, {
        allowMultiple:     true,
        maxFiles:          5,
        maxFileSize:       '500MB',
        acceptedFileTypes: [
            ...ACCEPTED_TYPES.image,
            ...ACCEPTED_TYPES.video,
            ...ACCEPTED_TYPES.audio,
            ...ACCEPTED_TYPES.pdf,
        ],
        labelIdle:     'Attach files <span class="filepond--label-action">browse</span>',
        beforeAddFile: heicConversionHook('image'),

        server: {
            process: (fieldName, file, metadata, load, error, progress, abort) => {
                let format = 'image';
                if (file.type.startsWith('video/'))       format = 'video';
                else if (file.type.startsWith('audio/'))  format = 'audio';
                else if (file.type === 'application/pdf') format = 'pdf';

                const processor = chunkedProcess({
                    format,
                    purpose:   'message',
                    modelType: currentModelType,
                    modelId:   currentModelId,
                    csrf,
                    onComplete: (finalPath) => {
                        if (onComplete) onComplete(finalPath);
                    },
                });

                return processor(fieldName, file, metadata, load, error, progress, abort);
            },
            revert: {
                url:     '/upload/revert',
                method:  'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf },
            },
        },
    });

    // Expose method to set modelId before browse
    pond.setModelId = (id, type = 'message') => {
        currentModelId   = id;
        currentModelType = type;
    };

    return pond;
};

