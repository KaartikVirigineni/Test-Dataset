using ImageGallery.Models;
using ImageGallery.Models.DTOs;

namespace ImageGallery.Services;

public interface IImageService
{
    Task<ImageDto?> UploadImageAsync(int userId, string title, string? description, IFormFile file);
    Task<IEnumerable<ImageDto>> GetAllImagesAsync();
    Task<IEnumerable<ImageDto>> GetUserImagesAsync(int userId);
    Task<ImageDto?> GetImageByIdAsync(int id);
    Task<bool> DeleteImageAsync(int id, int userId);
    Task<(byte[] content, string contentType, string fileName)?> GetImageFileAsync(int id);
}