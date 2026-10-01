using System.ComponentModel.DataAnnotations;

namespace ReserveIt.Models;

public class Reservation
{
    public int Id { get; set; }

    [Required]
    public int UserId { get; set; }

    public User? User { get; set; }

    [Required]
    public int TableId { get; set; }

    public Table? Table { get; set; }

    [Required]
    public DateTime ReservationDate { get; set; }

    [Required]
    public int DurationMinutes { get; set; }

    [Required]
    public int PartySize { get; set; }

    public string Status { get; set; } = "Confirmed";

    public string? SpecialRequests { get; set; }

    public DateTime CreatedAt { get; set; } = DateTime.UtcNow;
}