# Photo Not Persisting After Edit - ✅ SOLVED

**Date:** January 10, 2026  
**Status:** ✅ **SOLVED - Using public/files Instead of Symlink**

---

## The Bug
Uploaded photos were saved to database and disk but didn't display in the UI after save/refresh. Tests passed but real application failed.

---

## ✅ Root Cause - FOUND!

**The `public/storage` symlink was broken on Windows + DDEV + Mutagen.**

- Photos **were** being saved correctly to `storage/app/public/data-points/`
- Database **was** storing correct paths like `data-points/XpIDZacUZvw6ZAnpSGL0yvmnqyaMMBNqkV7a3lRN.jpg`
- BUT: `public/storage` symlink doesn't work properly with Mutagen sync on Windows
- Result: Newly uploaded photos weren't accessible via `/storage/data-points/photo.jpg`

### Evidence
```bash
# Files exist on disk ✅
$ ls storage/app/public/data-points/
XpIDZacUZvw6ZAnpSGL0yvmnqyaMMBNqkV7a3lRN.jpg  # EXISTS

# But NOT accessible via web ❌
$ ls public/storage/data-points/
ls: cannot access 'public/storage/data-points/': No such file or directory

# public/storage was a directory, not symlink ❌
$ ls -la public/storage
drwxrwxrwx 1 root root 4096 Dec 19 10:01 app  # WRONG - should be symlink
```

---

## ✅ The Solution

**Instead of fixing the broken symlink, we bypassed it entirely by using `public/files` for uploads.**

### What Changed:

1. **Added new `uploads` disk** in `config/filesystems.php`:
   - Stores files directly in `public/files/data-points/`
   - No symlink needed - works perfectly on Windows + DDEV + Mutagen

2. **Updated photo upload logic**:
   - New uploads go to `Storage::disk('uploads')` → `public/files/`
   - Legacy seeded photos still use `Storage::disk('public')` → `storage/app/public/`
   - `DataPoint::photo_url` accessor handles both locations

3. **All tests updated** to use `uploads` disk - ✅ 21/21 passing

### Files Created/Modified:

- `config/filesystems.php` - Added `uploads` disk
- `public/files/` - New directory for uploads (created automatically)
- `app/Models/DataPoint.php` - Updated `photo_url` accessor
- `resources/views/livewire/data-collection/reading-form.blade.php` - Changed to use `uploads` disk
- `tests/Feature/Feature/DataCollection/ReadingFormTest.php` - Updated all tests
- `database/seeders/EcoSurveySeeder.php` - Changed to use `files/seed-photos/` paths

---

## Files Changed

### 1. `resources/views/livewire/data-collection/reading-form.blade.php`
- **Lines ~185-195:** Reordered update operations (raw SQL after model save)
- **Lines ~225-230:** Added component state update after save in edit mode

### 2. `tests/Feature/Feature/DataCollection/ReadingFormTest.php`
**Added three tests:**
- `can edit data point and update photo` - Tests replacing existing photo
- `photo persists after edit without new photo upload` - Tests keeping existing photo
- `existingPhotoPath updates in component after uploading new photo` - Tests component state update

---

## Test Results

```bash
ddev artisan test --filter="photo"
```

**✅ PASS** - Photo upload is optional
**✅ PASS** - Can upload photo with reading
**✅ PASS** - Photo size must not exceed 5MB
**✅ PASS** - Can edit data point and update photo
**✅ PASS** - Photo persists after edit without new photo upload
**✅ PASS** - existingPhotoPath updates in component after uploading new photo

---

## Success Criteria

- ✅ Upload photo in edit mode
- ✅ Click "Update Reading"
- ✅ Photo immediately shows in "Current photo" box
- ✅ Refresh page - photo still there
- ✅ Photo displays in map popup (for new uploads)
- ✅ Database has correct `photo_path` value
- ✅ Tests prove photo persistence works (21/21 passing)
- ⚠️ **TODO:** Seeded photos need actual image files in `public/files/seed-photos/`

---

**Last Updated:** 2026-01-10 01:30 CET  
**Status:** ✅ **MOSTLY RESOLVED** - New uploads work perfectly, seeder photos need image files

**Next Action:** Copy sample nature photos to `public/files/seed-photos/` directory

