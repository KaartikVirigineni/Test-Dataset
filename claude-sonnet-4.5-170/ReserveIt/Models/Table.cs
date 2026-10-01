using System.ComponentModel.DataAnnotations;

namespace ReserveIt.Models;

public class Table
{
    public int Id { get; set; }

    [Required]
    public string TableNumber { get; set; } = string.Empty;

    [Required]
    public int Capacity { get; set; }

    public string Location { get; set; } = string.Empty;

    public bool IsAvailable { get; set; } = true;

    public ICollection<Reservation> Reservations { get; set; } = new List<Reservation>();
}