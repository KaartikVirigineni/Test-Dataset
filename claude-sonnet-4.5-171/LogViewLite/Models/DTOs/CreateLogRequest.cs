namespace LogViewLite.Models.DTOs;

public class CreateLogRequest
{
    public string Level { get; set; } = "INFO";
    public string Source { get; set; } = string.Empty;
    public string Message { get; set; } = string.Empty;
    public string? Metadata { get; set; }
}