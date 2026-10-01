namespace DevPortal.Models;

public class CreateResourceRequest
{
    public string Title { get; set; } = string.Empty;
    public string Description { get; set; } = string.Empty;
    public string? Url { get; set; }
    public string? Category { get; set; }
}