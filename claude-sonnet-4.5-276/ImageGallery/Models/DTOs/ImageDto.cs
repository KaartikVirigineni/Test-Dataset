namespace ImageGallery.Models.DTOs;

public class ImageDto
{
    public int Id { get; set; }
    public string Title { get; set; } = string.Empty;
    public string? Description { get; set; }
    public string FileName { get; set; } = string.Empty;
    public long FileSize { get; set; }
    public DateTime UploadedAt { get; set; }
    public int UserId { get; set; }
    public string Username { get; set; } = string.Empty;
}