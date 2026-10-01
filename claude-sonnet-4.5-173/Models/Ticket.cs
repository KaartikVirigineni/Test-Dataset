using System.ComponentModel.DataAnnotations;

namespace EventFlow.Models;

public class Ticket
{
    public int Id { get; set; }
    
    [Required]
    [MaxLength(50)]
    public string TicketNumber { get; set; } = string.Empty;
    
    public int EventId { get; set; }
    public Event? Event { get; set; }
    
    public int UserId { get; set; }
    public User? User { get; set; }
    
    [Required]
    public DateTime PurchaseDate { get; set; } = DateTime.UtcNow;
    
    [Required]
    [MaxLength(50)]
    public string Status { get; set; } = "Active";
}