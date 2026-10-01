using System.ComponentModel.DataAnnotations;

namespace EventFlow.Models;

public class Event
{
    public int Id { get; set; }
    
    [Required]
    [MaxLength(200)]
    public string Name { get; set; } = string.Empty;
    
    [MaxLength(2000)]
    public string Description { get; set; } = string.Empty;
    
    [MaxLength(300)]
    public string Location { get; set; } = string.Empty;
    
    [Required]
    public DateTime StartDate { get; set; }
    
    [Required]
    public DateTime EndDate { get; set; }
    
    [Required]
    public int TotalTickets { get; set; }
    
    [Required]
    public int AvailableTickets { get; set; }
    
    [Required]
    public decimal Price { get; set; }
    
    public int OrganizerId { get; set; }
    public User? Organizer { get; set; }
    
    public DateTime CreatedAt { get; set; } = DateTime.UtcNow;
}