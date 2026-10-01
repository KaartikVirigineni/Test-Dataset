using ImageGallery.Data;
using ImageGallery.Models;
using ImageGallery.Models.DTOs;
using Microsoft.EntityFrameworkCore;

namespace ImageGallery.Services;

public class ImageService : IImageService
{
    private readonly AppDbContext _context;
    private readonly string _uploadPath;

    public ImageService(AppDbContext context, IWebHostEnvironment environment)
    {
        _context = context;
        _uploadPath = Path.Combine(environment.ContentRootPath, "uploads");
        
        if (!Directory.Exists(_uploadPath))
        {
            Directory.CreateDirectory(_uploadPath);
        }
    }

    public async Task<ImageDto?> UploadImageAsync(int userId, string title, string? description, IFormFile file)
    {
        if (file == null || file.Length == 0)
            return null;

        var allowedExtensions = new[] { ".jpg", ".jpeg", ".png", ".gif", ".webp" };
        var extension = Path.GetExtension(file.FileName).ToLowerInvariant();
        
        if (!allowedExtensions.Contains(extension))
            return null;

        var fileName = $"{Guid.NewGuid()}{extension}";
        var filePath = Path.Combine(_uploadPath, fileName);

        using (var stream = new FileStream(filePath, FileMode.Create))
        {
            await file.CopyToAsync(stream);
        }

        var image = new Image
        {
            Title = title,
            Description = description,
            FileName = file.FileName,
            FilePath = filePath,
            FileSize = file.Length,
            UserId = userId,
            UploadedAt = DateTime.UtcNow
        };

        _context.Images.Add(image);
        await _context.SaveChangesAsync();

        return await GetImageByIdAsync(image.Id);
    }

    public async Task<IEnumerable<ImageDto>> GetAllImagesAsync()
    {
        return await _context.Images
            .Include(i => i.User)
            .OrderByDescending(i => i.UploadedAt)
            .Select(i => new ImageDto
            {
                Id = i.Id,
                Title = i.Title,
                Description = i.Description,
                FileName = i.FileName,
                FileSize = i.FileSize,
                UploadedAt = i.UploadedAt,
                UserId = i.UserId,
                Username = i.User.Username
            })
            .ToListAsync();
    }

    public async Task<IEnumerable<ImageDto>> GetUserImagesAsync(int userId)
    {
        return await _context.Images
            .Include(i => i.User)
            .Where(i => i.UserId == userId)
            .OrderByDescending(i => i.UploadedAt)
            .Select(i => new ImageDto
            {
                Id = i.Id,
                Title = i.Title,
                Description = i.Description,
                FileName = i.FileName,
                FileSize = i.FileSize,
                UploadedAt = i.UploadedAt,
                UserId = i.UserId,
                Username = i.User.Username
            })
            .ToListAsync();
    }

    public async Task<ImageDto?> GetImageByIdAsync(int id)
    {
        return await _context.Images
            .Include(i => i.User)
            .Where(i => i.Id == id)
            .Select(i => new ImageDto
            {
                Id = i.Id,
                Title = i.Title,
                Description = i.Description,
                FileName = i.FileName,
                FileSize = i.FileSize,
                UploadedAt = i.UploadedAt,
                UserId = i.UserId,
                Username = i.User.Username
            })
            .FirstOrDefaultAsync();
    }

    public async Task<bool> DeleteImageAsync(int id, int userId)
    {
        var image = await _context.Images.FindAsync(id);
        
        if (image == null || image.UserId != userId)
            return false;

        if (File.Exists(image.FilePath))
        {
            File.Delete(image.FilePath);
        }

        _context.Images.Remove(image);
        await _context.SaveChangesAsync();

        return true;
    }

    public async Task<(byte[] content, string contentType, string fileName)?> GetImageFileAsync(int id)
    {
        var image = await _context.Images.FindAsync(id);
        
        if (image == null || !File.Exists(image.FilePath))
            return null;

        var content = await File.ReadAllBytesAsync(image.FilePath);
        var extension = Path.GetExtension(image.FilePath).ToLowerInvariant();
        var contentType = extension switch
        {
            ".jpg" or ".jpeg" => "image/jpeg",
            ".png" => "image/png",
            ".gif" => "image/gif",
            ".webp" => "image/webp",
            _ => "application/octet-stream"
        };

        return (content, contentType, image.FileName);
    }
}