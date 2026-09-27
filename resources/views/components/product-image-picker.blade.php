@props([
    'id' => 'product_images',
    'max' => \App\Models\Product::MAX_IMAGES,
    'label' => 'Product Photos',
    'hint' => null,
])

{{-- Multi-file product image picker. Zero, one or many images are valid; the
     first upload becomes the product's main image. Pending selections can be
     removed before submit where the browser supports DataTransfer. --}}

@once
<style>
  .img-picker-previews {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(96px, 1fr));
    gap: 10px;
    margin-bottom: 14px;
  }
  .img-picker-previews:empty { display: none; }
  .img-picker-item {
    position: relative;
    aspect-ratio: 1 / 1;
    border: 1px solid #e2e8f0;
    border-radius: 10px;
    overflow: hidden;
    background: #f8fafc;
  }
  .img-picker-item img { width: 100%; height: 100%; object-fit: cover; display: block; }
  .img-picker-remove {
    position: absolute; top: 4px; right: 4px;
    width: 22px; height: 22px;
    display: flex; align-items: center; justify-content: center;
    background: rgba(15, 23, 42, 0.72);
    color: #fff; border: none; border-radius: 50%;
    font-size: 15px; line-height: 1; cursor: pointer;
    transition: background 0.2s;
  }
  .img-picker-remove:hover { background: #e11d48; }
  .img-picker-remove[hidden] { display: none; }
  .img-picker-tag {
    position: absolute; left: 0; bottom: 0;
    font-size: 10px; font-weight: 700; letter-spacing: 0.02em;
    background: rgba(15, 23, 42, 0.72); color: #fff;
    padding: 3px 7px; border-top-right-radius: 8px;
  }
  .img-picker-summary {
    font-size: 12px; font-weight: 600; color: #166534;
    margin-bottom: 10px;
  }
  .img-picker-summary:empty { display: none; }
</style>

<script>
(function () {
  if (window.aquiImagePickerReady) return;
  window.aquiImagePickerReady = true;

  // DataTransfer lets us rewrite the input's FileList so a single pending
  // image can be dropped. Not every browser allows constructing one, so the
  // remove buttons hide themselves when it is unavailable rather than
  // appearing to work while still uploading the file.
  function canRewriteFileList() {
    try {
      return typeof DataTransfer === 'function' && new DataTransfer() !== null;
    } catch (e) {
      return false;
    }
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-image-picker]').forEach(function (picker) {
      var input = picker.querySelector('input[type="file"]');
      var previews = picker.querySelector('[data-previews]');
      var summary = picker.querySelector('[data-summary]');
      var max = parseInt(picker.getAttribute('data-max'), 10);
      if (isNaN(max) || max < 1) max = 10;
      var writable = canRewriteFileList();

      if (!input || !previews) return;

      // Browsers without DataTransfer keep the full selection; the server
      // still enforces the cap, so this is a convenience limit only.
      function truncateToMax(files) {
        if (!writable || !files || files.length <= max) return files;
        var dt = new DataTransfer();
        Array.prototype.forEach.call(files, function (f, i) {
          if (i < max) dt.items.add(f);
        });
        input.files = dt.files;
        return input.files;
      }

      function render(files) {
        previews.innerHTML = '';
        var imageIndex = 0;

        Array.prototype.forEach.call(files, function (file, index) {
          if (!file.type || file.type.indexOf('image/') !== 0) return;

          var item = document.createElement('div');
          item.className = 'img-picker-item';

          var img = document.createElement('img');
          img.alt = file.name;
          var reader = new FileReader();
          reader.onload = function (e) { img.src = e.target.result; };
          reader.readAsDataURL(file);
          item.appendChild(img);

          if (imageIndex === 0) {
            var tag = document.createElement('span');
            tag.className = 'img-picker-tag';
            tag.textContent = 'MAIN';
            item.appendChild(tag);
          }
          imageIndex++;

          var remove = document.createElement('button');
          remove.type = 'button';
          remove.className = 'img-picker-remove';
          remove.setAttribute('aria-label', 'Remove ' + file.name);
          remove.innerHTML = '&times;';
          remove.hidden = !writable;
          remove.addEventListener('click', function () {
            var remaining = [];
            Array.prototype.forEach.call(input.files, function (f, i) {
              if (i !== index) remaining.push(f);
            });
            var dt = new DataTransfer();
            remaining.forEach(function (f) { dt.items.add(f); });
            input.files = dt.files;
            render(input.files);
          });

          item.appendChild(remove);

          previews.appendChild(item);
        });

        var count = input.files ? input.files.length : 0;
        if (summary) {
          summary.textContent = count === 0
            ? ''
            : count + (count === 1 ? ' image selected' : ' images selected')
              + (count >= max ? ' (maximum ' + max + ')' : '');
        }
      }

      input.addEventListener('change', function () {
        render(truncateToMax(input.files));
      });
    });
  });
})();
</script>
@endonce

<div class="form-group full-width" data-image-picker data-max="{{ $max }}">
    <label for="{{ $id }}">{{ $label }}</label>

    <div class="img-picker-previews" data-previews></div>
    <div class="img-picker-summary" data-summary></div>

    <div class="image-management-wrapper">
        <label class="file-dropzone" for="{{ $id }}">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>
            <div style="font-size: 14px; font-weight: 600; color: #334155;">Click to upload images</div>
            <div class="hint" style="margin-top: 4px;">{{ $hint ?? 'PNG, JPG, WebP or GIF up to 5MB each · up to ' . $max . ' images' }}</div>
            <input type="file" id="{{ $id }}" name="images[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple style="display: none;">
        </label>
    </div>
    <span class="hint">Optional. The first image is used as the catalog cover photo.</span>
</div>
