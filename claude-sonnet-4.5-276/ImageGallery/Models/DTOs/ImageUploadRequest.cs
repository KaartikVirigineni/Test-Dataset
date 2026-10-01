namespace ImageGallery.Models.DTOs;

public class ImageUploadRequest
{
    public string Title { get; set; } = string.Empty;
    public string? Description { get; set; }
    public IFormFile File { get; set; } = null!;
}