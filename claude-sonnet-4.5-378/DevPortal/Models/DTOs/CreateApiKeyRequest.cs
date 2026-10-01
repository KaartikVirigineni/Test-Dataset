namespace DevPortal.Models.DTOs;

public class CreateApiKeyRequest
{
    public string Name { get; set; } = string.Empty;
    public int? ExpiryInDays { get; set; }
}