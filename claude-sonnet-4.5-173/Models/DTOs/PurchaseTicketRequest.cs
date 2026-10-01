using System.ComponentModel.DataAnnotations;

namespace EventFlow.Models.DTOs;

public class PurchaseTicketRequest
{
    [Required]
    public int EventId { get; set; }
}